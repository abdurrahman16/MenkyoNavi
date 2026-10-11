<?php

namespace App\Services\Editorial;

use App\Enums\AnswerKeyReviewStatus;
use App\Enums\ContentRightsReviewStatus;
use App\Enums\StagedTranslationStatus;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportReviewAudit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Separate human release gates for original Karimen candidates.
 *
 * Accuracy and source-revision decisions remain in their dedicated services.
 * This service never fabricates approvals and never auto-publishes.
 */
class OriginalKarimenReleaseGateService
{
    public const EDITORIAL_AUDIT_ACTION = 'human_editorial_approval_decision';

    public const ANSWER_KEY_AUDIT_ACTION = 'original_answer_key_verification_decision';

    public const RIGHTS_AUDIT_ACTION = 'original_rights_clearance_decision';

    public const JAPANESE_READY_AUDIT_ACTION = 'original_japanese_ready_for_publication';

    public function __construct(
        private OriginalKarimenPilotAccuracyReviewService $accuracyReviews,
        private OriginalKarimenPilotSourceRevisionService $revisions,
        private EditorialReviewService $editorial,
    ) {}

    /**
     * @param  array{
     *     approved: bool,
     *     notes: string,
     *     expected_content_fingerprint: string,
     *     confirm_approval?: bool
     * }  $payload
     */
    public function submitEditorialDecision(QuestionImportEntry $entry, User $admin, array $payload): QuestionImportEntry
    {
        $this->assertAuthorized($admin, $entry);
        $this->hitRateLimit($admin, $entry, 'editorial');

        $notes = trim((string) $payload['notes']);
        $approved = (bool) $payload['approved'];
        $expected = (string) $payload['expected_content_fingerprint'];

        if (strlen($notes) < 12) {
            throw ValidationException::withMessages([
                'editorialNotes' => '編集承認には12文字以上の根拠メモが必要です。',
            ]);
        }

        if ($approved && ! ($payload['confirm_approval'] ?? false)) {
            throw ValidationException::withMessages([
                'confirmEditorialApproval' => '編集承認には明示的な確認が必要です。',
            ]);
        }

        return DB::transaction(function () use ($entry, $admin, $notes, $approved, $expected): QuestionImportEntry {
            $entry = QuestionImportEntry::query()->whereKey($entry->id)->lockForUpdate()->firstOrFail();
            $this->accuracyReviews->assertReviewableEntry($entry);
            $fingerprint = $this->accuracyReviews->reviewContentFingerprint($entry->loadMissing('translations'));

            if ($fingerprint !== $expected) {
                throw ValidationException::withMessages([
                    'expectedContentFingerprint' => '内容が更新されています。再読み込みしてから再度判定してください。',
                ]);
            }

            $metadata = $entry->source_metadata ?? [];
            $before = [
                'human_editorial_approval' => data_get($metadata, 'human_editorial_approval'),
                'content_fingerprint' => $fingerprint,
            ];

            $metadata['human_editorial_approval'] = $approved;
            $metadata['human_editorial_reviewed_content_fingerprint'] = $fingerprint;
            $metadata['last_human_editorial_reviewer_id'] = $admin->id;
            $metadata['last_human_editorial_reviewed_at'] = now()->toIso8601String();
            $metadata['publication_eligible'] = false;
            $metadata['public_exam_eligible'] = false;

            $flags = collect($entry->review_flags ?? [])
                ->reject(fn (string $flag): bool => in_array($flag, [
                    'awaits_human_editorial_approval',
                    'human_editorial_approved',
                    'human_editorial_withheld',
                ], true))
                ->when($approved, fn ($c) => $c->push('human_editorial_approved'))
                ->when(! $approved, fn ($c) => $c->push('awaits_human_editorial_approval')->push('human_editorial_withheld'))
                ->unique()
                ->values()
                ->all();

            $entry->forceFill([
                'source_metadata' => $metadata,
                'review_flags' => $flags,
                'is_published' => false,
            ]);
            $entry->publication_blockers = $entry->computePublicationBlockers();
            $entry->save();

            QuestionImportReviewAudit::query()->create([
                'question_import_entry_id' => $entry->id,
                'user_id' => $admin->id,
                'action' => self::EDITORIAL_AUDIT_ACTION,
                'before' => $before,
                'after' => [
                    'human_editorial_approval' => $approved,
                    'reviewed_content_fingerprint' => $fingerprint,
                    'confers_accuracy_approval' => false,
                    'confers_answer_key_verification' => false,
                    'confers_rights_clearance' => false,
                    'confers_publication' => false,
                ],
                'notes' => $notes,
            ]);

            return $entry->fresh(['translations', 'batch']);
        });
    }

    /**
     * @param  array{
     *     verified: bool,
     *     notes: string,
     *     reference: string,
     *     expected_content_fingerprint: string
     * }  $payload
     */
    public function submitAnswerKeyDecision(QuestionImportEntry $entry, User $admin, array $payload): QuestionImportEntry
    {
        $this->assertAuthorized($admin, $entry);
        $this->hitRateLimit($admin, $entry, 'answer-key');

        $notes = trim((string) $payload['notes']);
        $reference = trim((string) $payload['reference']);
        $verified = (bool) $payload['verified'];
        $expected = (string) $payload['expected_content_fingerprint'];

        if (strlen($notes) < 12) {
            throw ValidationException::withMessages([
                'answerKeyNotes' => '解答キー判定には12文字以上の根拠メモが必要です。',
            ]);
        }

        if ($verified && $reference === '') {
            throw ValidationException::withMessages([
                'answerKeyReference' => '解答キー検証には法令参照が必要です。',
            ]);
        }

        $this->assertCurrentFingerprint($entry, $expected);

        $status = $verified
            ? AnswerKeyReviewStatus::Verified
            : AnswerKeyReviewStatus::RequiresFurtherResearch;

        $updated = $this->editorial->updateAnswerKeyReview(
            $entry,
            $admin,
            $status,
            $notes,
            $reference !== '' ? $reference : $entry->answer_key_reference,
            null,
            null,
        );

        $flags = collect($updated->review_flags ?? [])
            ->reject(fn (string $flag): bool => in_array($flag, ['answer_key_verified_flag', 'answer_key_unverified_flag'], true))
            ->when($verified, fn ($c) => $c->push('answer_key_verified_flag'))
            ->unique()
            ->values()
            ->all();

        $updated->forceFill(['review_flags' => $flags])->save();
        $updated->publication_blockers = $updated->computePublicationBlockers();
        $updated->save();

        QuestionImportReviewAudit::query()->create([
            'question_import_entry_id' => $updated->id,
            'user_id' => $admin->id,
            'action' => self::ANSWER_KEY_AUDIT_ACTION,
            'before' => ['answer_key_status' => $entry->answer_key_status?->value],
            'after' => [
                'answer_key_status' => $updated->answer_key_status->value,
                'reviewed_content_fingerprint' => $expected,
                'confers_editorial_approval' => false,
                'confers_publication' => false,
            ],
            'notes' => $notes,
        ]);

        return $updated->fresh(['translations', 'batch']);
    }

    /**
     * @param  array{
     *     cleared: bool,
     *     notes: string,
     *     evidence: string,
     *     expected_content_fingerprint: string
     * }  $payload
     */
    public function submitRightsDecision(QuestionImportEntry $entry, User $admin, array $payload): QuestionImportEntry
    {
        $this->assertAuthorized($admin, $entry);
        $this->hitRateLimit($admin, $entry, 'rights');

        $notes = trim((string) $payload['notes']);
        $evidence = trim((string) $payload['evidence']);
        $cleared = (bool) $payload['cleared'];
        $expected = (string) $payload['expected_content_fingerprint'];

        if (strlen($notes) < 12) {
            throw ValidationException::withMessages([
                'rightsNotes' => '権利判定には12文字以上の根拠メモが必要です。',
            ]);
        }

        if ($cleared && strlen($evidence) < 12) {
            throw ValidationException::withMessages([
                'rightsEvidence' => '公開再利用の承認には権利根拠が必要です。',
            ]);
        }

        $this->assertCurrentFingerprint($entry, $expected);

        $status = $cleared
            ? ContentRightsReviewStatus::ApprovedForPublicReuse
            : ContentRightsReviewStatus::ReviewPending;

        $updated = $this->editorial->updateContentRights(
            $entry,
            $admin,
            $status,
            $notes,
            $evidence !== '' ? $evidence : $entry->content_rights_evidence,
        );

        $flags = collect($updated->review_flags ?? [])
            ->reject(fn (string $flag): bool => in_array($flag, ['rights_cleared_flag', 'rights_uncleared_flag'], true))
            ->when($cleared, fn ($c) => $c->push('rights_cleared_flag'))
            ->unique()
            ->values()
            ->all();

        $updated->forceFill(['review_flags' => $flags])->save();
        $updated->publication_blockers = $updated->computePublicationBlockers();
        $updated->save();

        QuestionImportReviewAudit::query()->create([
            'question_import_entry_id' => $updated->id,
            'user_id' => $admin->id,
            'action' => self::RIGHTS_AUDIT_ACTION,
            'before' => ['content_rights_status' => $entry->content_rights_status?->value],
            'after' => [
                'content_rights_status' => $updated->content_rights_status->value,
                'reviewed_content_fingerprint' => $expected,
                'confers_editorial_approval' => false,
                'confers_publication' => false,
            ],
            'notes' => $notes,
        ]);

        return $updated->fresh(['translations', 'batch']);
    }

    /**
     * @param  array{
     *     notes: string,
     *     expected_content_fingerprint: string,
     *     confirm_ready?: bool
     * }  $payload
     */
    public function markJapaneseReadyForPublication(QuestionImportEntry $entry, User $admin, array $payload): QuestionImportEntry
    {
        $this->assertAuthorized($admin, $entry);
        $this->hitRateLimit($admin, $entry, 'ja-ready');

        $notes = trim((string) $payload['notes']);
        $expected = (string) $payload['expected_content_fingerprint'];

        if (strlen($notes) < 12) {
            throw ValidationException::withMessages([
                'japaneseReadyNotes' => '日本語公開準備には12文字以上の根拠メモが必要です。',
            ]);
        }

        if (! ($payload['confirm_ready'] ?? false)) {
            throw ValidationException::withMessages([
                'confirmJapaneseReady' => '日本語を公開準備完了にするには明示的な確認が必要です。',
            ]);
        }

        return DB::transaction(function () use ($entry, $admin, $notes, $expected): QuestionImportEntry {
            $entry = QuestionImportEntry::query()->whereKey($entry->id)->lockForUpdate()->firstOrFail();
            $this->accuracyReviews->assertReviewableEntry($entry);
            $entry->loadMissing('translations');
            $fingerprint = $this->accuracyReviews->reviewContentFingerprint($entry);

            if ($fingerprint !== $expected) {
                throw ValidationException::withMessages([
                    'expectedContentFingerprint' => '内容が更新されています。再読み込みしてから再度判定してください。',
                ]);
            }

            if ($this->revisions->hasProposal($entry) && ! $this->revisions->isSourceClarificationResolved($entry)) {
                throw ValidationException::withMessages([
                    'japaneseReadyNotes' => '未解決のソース改訂がある候補は日本語公開準備にできません。',
                ]);
            }

            $japanese = $entry->translationFor('ja');
            if ($japanese === null || ! $japanese->hasCompleteText()) {
                throw ValidationException::withMessages([
                    'japaneseReadyNotes' => '完全な日本語設問・解説が必要です。',
                ]);
            }

            $beforeStatus = $japanese->status->value;
            $japanese->forceFill([
                'status' => StagedTranslationStatus::ReadyForPublication,
                'reviewed_at' => now(),
            ])->save();

            $metadata = $entry->source_metadata ?? [];
            $metadata['translation_reviewed'] = true;
            $metadata['japanese_ready_for_publication_fingerprint'] = $fingerprint;
            $metadata['japanese_ready_for_publication_by'] = $admin->id;
            $metadata['japanese_ready_for_publication_at'] = now()->toIso8601String();

            $flags = collect($entry->review_flags ?? [])
                ->reject(fn (string $flag): bool => in_array($flag, [
                    'translation_not_ready',
                    'japanese_ready_for_publication',
                ], true))
                ->push('japanese_ready_for_publication')
                ->unique()
                ->values()
                ->all();

            $entry->forceFill([
                'source_metadata' => $metadata,
                'review_flags' => $flags,
                'is_published' => false,
            ]);
            $entry->publication_blockers = $entry->computePublicationBlockers();
            $entry->save();

            QuestionImportReviewAudit::query()->create([
                'question_import_entry_id' => $entry->id,
                'user_id' => $admin->id,
                'action' => self::JAPANESE_READY_AUDIT_ACTION,
                'before' => ['japanese_status' => $beforeStatus],
                'after' => [
                    'japanese_status' => StagedTranslationStatus::ReadyForPublication->value,
                    'reviewed_content_fingerprint' => $fingerprint,
                    'confers_accuracy_approval' => false,
                    'confers_editorial_approval' => false,
                    'confers_publication' => false,
                ],
                'notes' => $notes,
            ]);

            return $entry->fresh(['translations', 'batch']);
        });
    }

    public function editorialApprovedForCurrentVersion(QuestionImportEntry $entry): bool
    {
        return (bool) data_get($entry->source_metadata, 'human_editorial_approval')
            && data_get($entry->source_metadata, 'human_editorial_reviewed_content_fingerprint')
                === $this->accuracyReviews->reviewContentFingerprint($entry);
    }

    private function assertAuthorized(User $admin, QuestionImportEntry $entry): void
    {
        if (! $admin->isAdministrator() || $admin->email_verified_at === null) {
            throw ValidationException::withMessages([
                'gate' => '認証済み管理者のみが公開ゲートを操作できます。',
            ]);
        }

        $this->accuracyReviews->assertReviewableEntry($entry);
    }

    private function assertCurrentFingerprint(QuestionImportEntry $entry, string $expected): void
    {
        $fingerprint = $this->accuracyReviews->reviewContentFingerprint($entry->loadMissing('translations'));

        if ($fingerprint !== $expected) {
            throw ValidationException::withMessages([
                'expectedContentFingerprint' => '内容が更新されています。再読み込みしてから再度判定してください。',
            ]);
        }
    }

    private function hitRateLimit(User $admin, QuestionImportEntry $entry, string $scope): void
    {
        $key = 'original-release-gate:'.$scope.':'.$admin->id.':'.$entry->id;

        if (RateLimiter::tooManyAttempts($key, 20)) {
            throw ValidationException::withMessages([
                'gate' => '操作が多すぎます。しばらくしてから再試行してください。',
            ]);
        }

        RateLimiter::hit($key, 60);
    }
}
