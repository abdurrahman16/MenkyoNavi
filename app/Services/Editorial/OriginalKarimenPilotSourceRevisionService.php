<?php

namespace App\Services\Editorial;

use App\Enums\AccuracyEvidenceStatus;
use App\Enums\SourceRevisionDecision;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportReviewAudit;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Controlled Japanese source-revision proposals and human revision decisions
 * for original Karimen pilot (:1, :8) and expansion (:26, :34, :49) clarification candidates.
 *
 * Applying a revision never grants accuracy / editorial / rights / publication approval.
 */
class OriginalKarimenPilotSourceRevisionService
{
    public const COLLECTION_KEY = OriginalKarimenPilotStagingService::COLLECTION_KEY;

    public const SOURCE_GROUP = OriginalKarimenPilotStagingService::SOURCE_GROUP;

    public const EXPANSION_COLLECTION_KEY = OriginalKarimenExpansionStagingService::COLLECTION_KEY;

    public const EXPANSION_SOURCE_GROUP = OriginalKarimenExpansionStagingService::SOURCE_GROUP;

    public const AUDIT_ACTION = 'source_revision_decision';

    public const DATA_RELATIVE_PATH = 'data/original_karimen_pilot_001_source_revisions.php';

    public const EXPANSION_DATA_RELATIVE_PATH = 'data/original_karimen_expansion_001_source_revisions.php';

    public const TARGET_NUMBERS = [1, 8];

    public const EXPANSION_TARGET_NUMBERS = [26, 34, 49];

    /**
     * @return array{
     *     collection_key: string,
     *     prepared_at: string,
     *     sources_accessed_at: string,
     *     law_version_note: string,
     *     sources_inspected: list<array{label: string, url: string, accessed_at: string}>,
     *     proposals: array<int, array<string, mixed>>,
     *     human_revision_decisions_recorded: int
     * }
     */
    public function catalog(): array
    {
        return $this->loadCatalog(self::DATA_RELATIVE_PATH, self::COLLECTION_KEY, self::TARGET_NUMBERS, 'pilot');
    }

    /**
     * @return array{
     *     collection_key: string,
     *     prepared_at: string,
     *     sources_accessed_at: string,
     *     law_version_note: string,
     *     sources_inspected: list<array{label: string, url: string, accessed_at: string}>,
     *     proposals: array<int, array<string, mixed>>,
     *     human_revision_decisions_recorded: int
     * }
     */
    public function expansionCatalog(): array
    {
        return $this->loadCatalog(
            self::EXPANSION_DATA_RELATIVE_PATH,
            self::EXPANSION_COLLECTION_KEY,
            self::EXPANSION_TARGET_NUMBERS,
            'expansion',
        );
    }

    /**
     * @param  list<int>  $requiredNumbers
     * @return array<string, mixed>
     */
    private function loadCatalog(string $relativePath, string $collectionKey, array $requiredNumbers, string $label): array
    {
        $path = database_path($relativePath);

        if (! is_file($path)) {
            throw new RuntimeException("Original Karimen {$label} source-revision catalog is missing: {$path}");
        }

        /** @var array<string, mixed> $catalog */
        $catalog = require $path;

        if (($catalog['collection_key'] ?? null) !== $collectionKey) {
            throw new RuntimeException("Source-revision catalog collection_key mismatch for {$label}.");
        }

        foreach ($requiredNumbers as $number) {
            if (! is_array($catalog['proposals'][$number] ?? null)) {
                throw new RuntimeException("Missing source-revision proposal for {$label} candidate #{$number}.");
            }
        }

        return $catalog;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function proposalForNumber(int $number): ?array
    {
        $catalog = $this->catalog();

        return is_array($catalog['proposals'][$number] ?? null)
            ? $catalog['proposals'][$number]
            : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function proposalForEntry(QuestionImportEntry $entry): ?array
    {
        $number = (int) $entry->original_question_number;

        if ($entry->source_group === self::SOURCE_GROUP) {
            return $this->proposalForNumber($number);
        }

        if ($entry->source_group === self::EXPANSION_SOURCE_GROUP) {
            $catalog = $this->expansionCatalog();

            return is_array($catalog['proposals'][$number] ?? null)
                ? $catalog['proposals'][$number]
                : null;
        }

        return null;
    }

    public function hasProposal(QuestionImportEntry $entry): bool
    {
        return $this->proposalForEntry($entry) !== null;
    }

    public function isSourceClarificationResolved(QuestionImportEntry $entry): bool
    {
        if (! $this->hasProposal($entry)) {
            return true;
        }

        if (! (bool) data_get($entry->source_metadata, 'source_clarification_resolved')) {
            return false;
        }

        $decision = SourceRevisionDecision::tryFrom((string) data_get($entry->source_metadata, 'source_revision_decision', ''));

        if ($decision === SourceRevisionDecision::ApplyProposedRevision) {
            return data_get($entry->source_metadata, 'source_revision_applied_content_fingerprint')
                === $this->accuracyReviews()->reviewContentFingerprint($entry);
        }

        // Authenticated rejection keeps the current wording and resolves the withhold.
        return $decision === SourceRevisionDecision::RejectProposedRevision;
    }

    public function translationEligibilityLabel(QuestionImportEntry $entry): string
    {
        if (! $this->hasProposal($entry)) {
            return 'not_applicable';
        }

        if ($this->isSourceClarificationResolved($entry)) {
            return 'eligible_after_source_resolution';
        }

        return 'withheld_pending_source_revision';
    }

    /**
     * @return Collection<int, QuestionImportReviewAudit>
     */
    public function revisionHistory(QuestionImportEntry $entry): Collection
    {
        return QuestionImportReviewAudit::query()
            ->with('user:id,name,email')
            ->where('question_import_entry_id', $entry->id)
            ->where('action', self::AUDIT_ACTION)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param  array{
     *     decision: SourceRevisionDecision,
     *     notes: string,
     *     expected_content_fingerprint: string,
     *     confirm_revision_apply?: bool
     * }  $payload
     */
    public function submitRevisionDecision(QuestionImportEntry $entry, User $admin, array $payload): QuestionImportEntry
    {
        if (! $admin->isAdministrator()) {
            throw ValidationException::withMessages([
                'revision_decision' => '認証済み管理者のみがソース改訂判定を記録できます。',
            ]);
        }

        $this->accuracyReviews()->assertPilotEntry($entry);

        if (! $this->hasProposal($entry)) {
            throw ValidationException::withMessages([
                'revision_decision' => 'この候補には登録済みのソース改訂提案がありません。',
            ]);
        }

        $rateKey = 'pilot-source-revision:'.$admin->id.':'.$entry->id;
        if (RateLimiter::tooManyAttempts($rateKey, 20)) {
            throw ValidationException::withMessages([
                'revision_decision' => '短時間の送信が多すぎます。しばらくしてから再試行してください。',
            ]);
        }
        RateLimiter::hit($rateKey, 60);

        $decision = $payload['decision'];
        $notes = trim((string) $payload['notes']);
        $expectedFingerprint = (string) $payload['expected_content_fingerprint'];
        $entry->loadMissing('translations');
        $currentFingerprint = $this->accuracyReviews()->reviewContentFingerprint($entry);

        if ($expectedFingerprint === '' || ! hash_equals($currentFingerprint, $expectedFingerprint)) {
            throw ValidationException::withMessages([
                'expected_content_fingerprint' => 'この候補の内容が更新されています。ページを再読み込みしてから再度判定してください。',
            ]);
        }

        if ($notes === '' || mb_strlen($notes) < 12) {
            throw ValidationException::withMessages([
                'revision_notes' => '改訂判定メモは12文字以上で、根拠または判断理由を具体的に記入してください。',
            ]);
        }

        if ($decision->requiresExplicitConfirmation() && ! ($payload['confirm_revision_apply'] ?? false)) {
            throw ValidationException::withMessages([
                'confirm_revision_apply' => '提案改訂の適用には明示的な確認が必要です。',
            ]);
        }

        $proposal = $this->proposalForEntry($entry);
        if ($proposal === null) {
            throw ValidationException::withMessages([
                'revision_decision' => '改訂提案データが見つかりません。',
            ]);
        }

        $duplicate = QuestionImportReviewAudit::query()
            ->where('question_import_entry_id', $entry->id)
            ->where('user_id', $admin->id)
            ->where('action', self::AUDIT_ACTION)
            ->where('created_at', '>=', now()->subSeconds(5))
            ->latest('id')
            ->first();

        if ($duplicate !== null
            && data_get($duplicate->after, 'source_revision_decision') === $decision->value
            && data_get($duplicate->after, 'content_fingerprint_before') === $currentFingerprint
            && trim((string) $duplicate->notes) === $notes) {
            return $entry->fresh(['translations', 'batch', 'accuracyReviewer']);
        }

        return DB::transaction(function () use ($entry, $admin, $decision, $notes, $currentFingerprint, $proposal): QuestionImportEntry {
            $entry = QuestionImportEntry::query()->whereKey($entry->id)->lockForUpdate()->firstOrFail();
            $this->accuracyReviews()->assertPilotEntry($entry);
            $entry->loadMissing('translations');

            if ($this->accuracyReviews()->reviewContentFingerprint($entry) !== $currentFingerprint) {
                throw ValidationException::withMessages([
                    'expected_content_fingerprint' => 'この候補の内容が更新されています。ページを再読み込みしてから再度判定してください。',
                ]);
            }

            $japanese = $entry->translationFor('ja');
            if ($japanese === null) {
                throw ValidationException::withMessages([
                    'revision_decision' => '日本語ソースがありません。',
                ]);
            }

            $previousQuestion = (string) $japanese->question_text;
            $previousExplanation = (string) $japanese->explanation;
            $previousAnswer = (bool) $entry->correct_answer;
            $metadata = $entry->source_metadata ?? [];
            $previousDecision = data_get($metadata, 'source_revision_decision');
            $history = collect(data_get($metadata, 'source_revision_history', []))
                ->filter(fn ($row): bool => is_array($row))
                ->values()
                ->all();

            $appliedFingerprint = $currentFingerprint;
            $contentChanged = false;

            if ($decision->appliesContentChange()) {
                $newQuestion = (string) $proposal['proposed_question_ja'];
                $newExplanation = (string) $proposal['proposed_explanation_ja'];
                $newAnswer = (bool) $proposal['proposed_correct_answer'];

                if ($newQuestion === '' || $newExplanation === '') {
                    throw ValidationException::withMessages([
                        'revision_decision' => '提案設問または解説が空のため適用できません。',
                    ]);
                }

                $japanese->forceFill([
                    'question_text' => $newQuestion,
                    'explanation' => $newExplanation,
                ])->save();

                $entry->forceFill([
                    'correct_answer' => $newAnswer,
                    'content_fingerprint' => hash('sha256', $newQuestion.'|'.($newAnswer ? '1' : '0').'|'.$newExplanation),
                    'question_text_fingerprint' => hash('sha256', $newQuestion),
                    'english_explanation' => '[Non-canonical gloss; Japanese revised '.$decision->value.'; re-gloss pending] '.$entry->english_explanation,
                ]);

                $contentChanged = $previousQuestion !== $newQuestion
                    || $previousExplanation !== $newExplanation
                    || $previousAnswer !== $newAnswer;

                $history[] = [
                    'recorded_at' => now()->toIso8601String(),
                    'reviewer_id' => $admin->id,
                    'decision' => $decision->value,
                    'previous_question_ja' => $previousQuestion,
                    'previous_explanation_ja' => $previousExplanation,
                    'previous_correct_answer' => $previousAnswer,
                    'previous_content_fingerprint' => $currentFingerprint,
                    'revised_question_ja' => $newQuestion,
                    'revised_explanation_ja' => $newExplanation,
                    'revised_correct_answer' => $newAnswer,
                ];

                $metadata['proposed_question_ja'] = $newQuestion;
                $metadata['proposed_question_applied'] = true;
                $metadata['source_clarification_resolved'] = true;
                $metadata['research_assessment'] = 'supported_as_written';
                $metadata['ambiguity'] = (string) ($proposal['remaining_ambiguity'] ?? data_get($metadata, 'ambiguity'));
                $metadata['exact_provision'] = (string) ($proposal['exact_provision'] ?? data_get($metadata, 'exact_provision'));
                $metadata['source_revision_applied_at'] = now()->toIso8601String();
                $metadata['source_revision_applied_by'] = $admin->id;

                // Fingerprint includes exact_provision metadata; set it before hashing.
                $entry->source_metadata = $metadata;
                $entry->loadMissing('translations');
                $appliedFingerprint = $this->accuracyReviews()->reviewContentFingerprint($entry);
                $metadata['source_revision_applied_content_fingerprint'] = $appliedFingerprint;
                $entry->source_metadata = $metadata;
            } else {
                // Reject keeps current wording but records an authenticated human resolution.
                // Request/defer leave the clarification unresolved for publication.
                $metadata['source_clarification_resolved'] = $decision === SourceRevisionDecision::RejectProposedRevision;
                $metadata['proposed_question_applied'] = false;
                $history[] = [
                    'recorded_at' => now()->toIso8601String(),
                    'reviewer_id' => $admin->id,
                    'decision' => $decision->value,
                    'previous_question_ja' => $previousQuestion,
                    'previous_explanation_ja' => $previousExplanation,
                    'previous_correct_answer' => $previousAnswer,
                    'previous_content_fingerprint' => $currentFingerprint,
                    'content_unchanged' => true,
                ];
            }

            $metadata['source_revision_decision'] = $decision->value;
            $metadata['source_revision_history'] = $history;
            $metadata['last_source_revision_reviewer_id'] = $admin->id;
            $metadata['last_source_revision_reviewed_at'] = now()->toIso8601String();
            $metadata['human_accuracy_review'] = false;
            $metadata['human_editorial_approval'] = false;
            $metadata['public_exam_eligible'] = false;
            $metadata['publication_eligible'] = false;

            if ($contentChanged) {
                $metadata['human_accuracy_approval_stale'] = (bool) data_get($entry->source_metadata, 'human_accuracy_review')
                    || filled(data_get($entry->source_metadata, 'human_accuracy_decision'));
                $metadata['human_accuracy_stale_reason'] = $metadata['human_accuracy_approval_stale']
                    ? 'Source revision changed content; prior accuracy decision does not cover the revised version.'
                    : data_get($metadata, 'human_accuracy_stale_reason');
                unset($metadata['human_accuracy_reviewed_content_fingerprint']);
                $metadata['human_accuracy_decision'] = null;
            }

            $flags = collect($entry->review_flags ?? [])
                ->reject(fn (string $flag): bool => in_array($flag, [
                    'needs_editorial_clarification',
                    'source_revision_applied',
                    'source_revision_requested_changes',
                    'source_revision_rejected',
                    'source_revision_deferred',
                    'human_accuracy_accepted',
                    'accuracy_approval_stale',
                ], true))
                ->push(match ($decision) {
                    SourceRevisionDecision::ApplyProposedRevision => 'source_revision_applied',
                    SourceRevisionDecision::RequestAdditionalChanges => 'source_revision_requested_changes',
                    SourceRevisionDecision::RejectProposedRevision => 'source_revision_rejected',
                    SourceRevisionDecision::DeferPendingEvidence => 'source_revision_deferred',
                })
                ->when(
                    in_array($decision, [
                        SourceRevisionDecision::RequestAdditionalChanges,
                        SourceRevisionDecision::DeferPendingEvidence,
                    ], true),
                    fn ($c) => $c->push('needs_editorial_clarification'),
                )
                ->when(
                    $contentChanged,
                    fn ($c) => $c->push('awaits_human_accuracy_review')->push('accuracy_approval_stale'),
                )
                ->unique()
                ->values()
                ->all();

            $before = [
                'question_ja' => $previousQuestion,
                'explanation_ja' => $previousExplanation,
                'correct_answer' => $previousAnswer,
                'content_fingerprint' => $currentFingerprint,
                'source_revision_decision' => $previousDecision,
                'human_accuracy_review' => data_get($entry->source_metadata, 'human_accuracy_review'),
                'human_editorial_approval' => data_get($entry->source_metadata, 'human_editorial_approval'),
                'is_published' => $entry->is_published,
                'answer_key_status' => $entry->answer_key_status?->value,
                'content_rights_status' => $entry->content_rights_status?->value,
            ];

            $entry->fill([
                'source_metadata' => $metadata,
                'review_flags' => $flags,
                'is_published' => false,
                'accuracy_evidence_status' => $contentChanged
                    ? AccuracyEvidenceStatus::NeedsClarification
                    : $entry->accuracy_evidence_status,
                'accuracy_verification_notes' => trim((string) $entry->accuracy_verification_notes)."\n\n[Source revision {$decision->value} by user #{$admin->id}]\n".$notes,
            ]);

            if ($contentChanged) {
                $entry->accuracy_reviewed_by = null;
                $entry->accuracy_reviewed_at = null;
            }

            $entry->publication_blockers = $entry->computePublicationBlockers();
            $entry->save();

            QuestionImportReviewAudit::query()->create([
                'question_import_entry_id' => $entry->id,
                'user_id' => $admin->id,
                'action' => self::AUDIT_ACTION,
                'before' => $before,
                'after' => [
                    'source_revision_decision' => $decision->value,
                    'content_changed' => $contentChanged,
                    'content_fingerprint_before' => $currentFingerprint,
                    'content_fingerprint_after' => $appliedFingerprint,
                    'question_ja' => $entry->translationFor('ja')?->question_text,
                    'explanation_ja' => $entry->translationFor('ja')?->explanation,
                    'correct_answer' => $entry->correct_answer,
                    'human_accuracy_review' => false,
                    'human_editorial_approval' => false,
                    'is_published' => false,
                    'answer_key_status' => $entry->answer_key_status->value,
                    'content_rights_status' => $entry->content_rights_status->value,
                    'promoted_question_id' => $entry->promoted_question_id,
                    'source_clarification_resolved' => (bool) data_get($metadata, 'source_clarification_resolved'),
                    'confers_accuracy_approval' => false,
                    'confers_editorial_approval' => false,
                    'confers_rights_clearance' => false,
                    'confers_publication' => false,
                    'candidate_id' => data_get($metadata, 'candidate_id'),
                    'previous_question_retained_in_history' => true,
                ],
                'notes' => $notes,
            ]);

            $fresh = $entry->fresh(['translations', 'batch', 'accuracyReviewer']);
            app(OriginalKarimenPilotTranslationDraftService::class)->refreshStaleTranslationFlags($fresh);

            return $fresh;
        });
    }

    private function accuracyReviews(): OriginalKarimenPilotAccuracyReviewService
    {
        return app(OriginalKarimenPilotAccuracyReviewService::class);
    }
}
