<?php

namespace App\Services\Editorial;

use App\Enums\AccuracyEvidenceStatus;
use App\Enums\HumanAccuracyReviewDecision;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportReviewAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Human accuracy-review decisions for original Karimen pilot and expansion collections.
 *
 * Accuracy acceptance never publishes, clears rights, verifies answer keys,
 * grants editorial approval, or promotes staged rows.
 */
class OriginalKarimenPilotAccuracyReviewService
{
    public const COLLECTION_KEY = OriginalKarimenPilotStagingService::COLLECTION_KEY;

    public const SOURCE_GROUP = OriginalKarimenPilotStagingService::SOURCE_GROUP;

    public const EXPANSION_COLLECTION_KEY = OriginalKarimenExpansionStagingService::COLLECTION_KEY;

    public const EXPANSION_SOURCE_GROUP = OriginalKarimenExpansionStagingService::SOURCE_GROUP;

    public const AUDIT_ACTION = 'human_accuracy_review_decision';

    /**
     * @return list<string>
     */
    public function allowedSourceGroups(): array
    {
        return [self::SOURCE_GROUP, self::EXPANSION_SOURCE_GROUP];
    }

    /**
     * @return Builder<QuestionImportEntry>
     */
    public function pilotEntriesQuery(): Builder
    {
        return QuestionImportEntry::query()
            ->where('source_group', self::SOURCE_GROUP)
            ->whereHas('batch', fn (Builder $batch) => $batch->where('source_key', self::COLLECTION_KEY));
    }

    /**
     * @return Builder<QuestionImportEntry>
     */
    public function originalEntriesQuery(): Builder
    {
        return QuestionImportEntry::query()
            ->whereIn('source_group', $this->allowedSourceGroups())
            ->whereHas('batch', fn (Builder $batch) => $batch->whereIn('source_key', [
                self::COLLECTION_KEY,
                self::EXPANSION_COLLECTION_KEY,
            ]));
    }

    public function assertPilotEntry(QuestionImportEntry $entry): void
    {
        $this->assertReviewableEntry($entry);
    }

    public function assertReviewableEntry(QuestionImportEntry $entry): void
    {
        $entry->loadMissing('batch');

        if (! in_array($entry->source_group, $this->allowedSourceGroups(), true)) {
            throw ValidationException::withMessages([
                'entry' => 'This accuracy-review workbench only accepts original Karimen pilot or expansion candidates.',
            ]);
        }

        $sourceKey = $entry->batch?->source_key;
        if (! in_array($sourceKey, [self::COLLECTION_KEY, self::EXPANSION_COLLECTION_KEY], true)) {
            throw ValidationException::withMessages([
                'entry' => 'Candidate is outside the original Karimen review collections.',
            ]);
        }

        if ($entry->source_group === self::SOURCE_GROUP && $sourceKey !== self::COLLECTION_KEY) {
            throw ValidationException::withMessages([
                'entry' => 'Candidate is outside the original Karimen pilot collection.',
            ]);
        }

        if ($entry->source_group === self::EXPANSION_SOURCE_GROUP && $sourceKey !== self::EXPANSION_COLLECTION_KEY) {
            throw ValidationException::withMessages([
                'entry' => 'Candidate is outside the original Karimen expansion collection.',
            ]);
        }
    }

    public function reviewContentFingerprint(QuestionImportEntry $entry): string
    {
        $japanese = $entry->translationFor('ja');

        return hash('sha256', implode("\n", [
            $entry->stableSourceId(),
            (string) ($japanese?->question_text ?? ''),
            $entry->correct_answer ? '1' : '0',
            (string) ($japanese?->explanation ?? ''),
            (string) ($entry->answer_key_reference ?? ''),
            (string) data_get($entry->source_metadata, 'exact_provision', ''),
        ]));
    }

    public function humanAccuracyDecision(QuestionImportEntry $entry): ?HumanAccuracyReviewDecision
    {
        $this->refreshStaleAccuracyApproval($entry);

        return HumanAccuracyReviewDecision::tryFrom((string) data_get($entry->source_metadata, 'human_accuracy_decision', ''));
    }

    public function humanAccuracyAcceptedForCurrentVersion(QuestionImportEntry $entry): bool
    {
        $this->refreshStaleAccuracyApproval($entry);

        return (bool) data_get($entry->source_metadata, 'human_accuracy_review')
            && data_get($entry->source_metadata, 'human_accuracy_reviewed_content_fingerprint') === $this->reviewContentFingerprint($entry)
            && $entry->accuracy_evidence_status === AccuracyEvidenceStatus::HumanReviewed;
    }

    public function refreshStaleAccuracyApproval(QuestionImportEntry $entry): QuestionImportEntry
    {
        if (! in_array($entry->source_group, $this->allowedSourceGroups(), true)) {
            return $entry;
        }

        $metadata = $entry->source_metadata ?? [];
        $reviewedFingerprint = data_get($metadata, 'human_accuracy_reviewed_content_fingerprint');
        $claimedAccepted = (bool) data_get($metadata, 'human_accuracy_review');

        if (! $claimedAccepted || ! is_string($reviewedFingerprint) || $reviewedFingerprint === '') {
            return $entry;
        }

        if ($reviewedFingerprint === $this->reviewContentFingerprint($entry)) {
            return $entry;
        }

        $metadata['human_accuracy_review'] = false;
        $metadata['human_accuracy_approval_stale'] = true;
        $metadata['human_accuracy_stale_reason'] = 'Content fingerprint changed after the recorded human accuracy decision.';
        $flags = collect($entry->review_flags ?? [])
            ->push('accuracy_approval_stale')
            ->unique()
            ->values()
            ->all();

        $entry->forceFill([
            'source_metadata' => $metadata,
            'review_flags' => $flags,
            'accuracy_evidence_status' => $entry->accuracy_evidence_status === AccuracyEvidenceStatus::HumanReviewed
                ? AccuracyEvidenceStatus::NeedsClarification
                : $entry->accuracy_evidence_status,
        ]);
        $entry->publication_blockers = $entry->computePublicationBlockers();
        $entry->save();

        return $entry->refresh();
    }

    /**
     * @return Collection<int, QuestionImportReviewAudit>
     */
    public function reviewHistory(QuestionImportEntry $entry): Collection
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
     *     decision: HumanAccuracyReviewDecision,
     *     notes: string,
     *     expected_content_fingerprint: string,
     *     confirm_acceptance?: bool,
     *     acknowledges_scope_note?: bool,
     *     acknowledges_proposed_stem?: bool
     * }  $payload
     */
    public function submitDecision(QuestionImportEntry $entry, User $admin, array $payload): QuestionImportEntry
    {
        if (! $admin->isAdministrator()) {
            throw ValidationException::withMessages([
                'decision' => 'Only authenticated administrators may submit accuracy-review decisions.',
            ]);
        }

        $this->assertPilotEntry($entry);

        $rateKey = 'pilot-accuracy-review:'.$admin->id.':'.$entry->id;
        if (RateLimiter::tooManyAttempts($rateKey, 20)) {
            throw ValidationException::withMessages([
                'decision' => 'Too many review submissions. Please wait and try again.',
            ]);
        }
        RateLimiter::hit($rateKey, 60);

        $decision = $payload['decision'];
        $notes = trim((string) $payload['notes']);
        $expectedFingerprint = (string) $payload['expected_content_fingerprint'];
        $currentFingerprint = $this->reviewContentFingerprint($entry->loadMissing('translations'));

        if ($expectedFingerprint === '' || ! hash_equals($currentFingerprint, $expectedFingerprint)) {
            throw ValidationException::withMessages([
                'expected_content_fingerprint' => 'この候補の内容が更新されています。ページを再読み込みしてから再度審査してください。',
            ]);
        }

        if ($notes === '' || mb_strlen($notes) < 12) {
            throw ValidationException::withMessages([
                'notes' => '審査メモは12文字以上で、根拠または判断理由を具体的に記入してください。',
            ]);
        }

        if ($decision === HumanAccuracyReviewDecision::Accepted) {
            if (! ($payload['confirm_acceptance'] ?? false)) {
                throw ValidationException::withMessages([
                    'confirm_acceptance' => '正確性承認には明示的な確認が必要です。',
                ]);
            }

            if ($entry->source_group === self::SOURCE_GROUP
                && (int) $entry->original_question_number === 1
                && ! ($payload['acknowledges_scope_note'] ?? false)) {
                throw ValidationException::withMessages([
                    'acknowledges_scope_note' => '候補 :1 では第7条の主体スコープ（歩行者等又は車両等）注意事項を確認してください。',
                ]);
            }

            $requiresProposedStemAck = (
                $entry->source_group === self::SOURCE_GROUP
                && (int) $entry->original_question_number === 8
            ) || (
                $entry->source_group === self::EXPANSION_SOURCE_GROUP
                && in_array((int) $entry->original_question_number, [26, 34, 49], true)
            );

            if ($requiresProposedStemAck && ! ($payload['acknowledges_proposed_stem'] ?? false)) {
                throw ValidationException::withMessages([
                    'acknowledges_proposed_stem' => '提案中のソース改訂設問を確認してください（未適用の改訂提案がある候補）。',
                ]);
            }
        }

        $duplicate = QuestionImportReviewAudit::query()
            ->where('question_import_entry_id', $entry->id)
            ->where('user_id', $admin->id)
            ->where('action', self::AUDIT_ACTION)
            ->where('created_at', '>=', now()->subSeconds(5))
            ->latest('id')
            ->first();

        if ($duplicate !== null
            && data_get($duplicate->after, 'human_accuracy_decision') === $decision->value
            && data_get($duplicate->after, 'reviewed_content_fingerprint') === $currentFingerprint
            && trim((string) $duplicate->notes) === $notes) {
            return $entry->fresh(['translations', 'batch', 'accuracyReviewer']);
        }

        return DB::transaction(function () use ($entry, $admin, $decision, $notes, $currentFingerprint): QuestionImportEntry {
            $entry = QuestionImportEntry::query()->whereKey($entry->id)->lockForUpdate()->firstOrFail();
            $this->assertPilotEntry($entry);

            if ($this->reviewContentFingerprint($entry->loadMissing('translations')) !== $currentFingerprint) {
                throw ValidationException::withMessages([
                    'expected_content_fingerprint' => 'この候補の内容が更新されています。ページを再読み込みしてから再度審査してください。',
                ]);
            }

            $previousDecision = data_get($entry->source_metadata, 'human_accuracy_decision');
            $previousStatus = $entry->accuracy_evidence_status?->value;
            $metadata = $entry->source_metadata ?? [];

            $metadata['human_accuracy_decision'] = $decision->value;
            $metadata['human_accuracy_review'] = $decision === HumanAccuracyReviewDecision::Accepted;
            $metadata['human_accuracy_reviewed_content_fingerprint'] = $currentFingerprint;
            $metadata['human_accuracy_approval_stale'] = false;
            $metadata['human_editorial_approval'] = false;
            $metadata['public_exam_eligible'] = false;
            $metadata['publication_eligible'] = false;
            $metadata['last_human_accuracy_reviewer_id'] = $admin->id;
            $metadata['last_human_accuracy_reviewed_at'] = now()->toIso8601String();

            $flags = collect($entry->review_flags ?? [])
                ->reject(fn (string $flag): bool => in_array($flag, [
                    'awaits_human_accuracy_review',
                    'accuracy_approval_stale',
                    'human_accuracy_accepted',
                    'human_accuracy_revision_requested',
                    'human_accuracy_rejected',
                    'human_accuracy_deferred',
                ], true))
                ->push(match ($decision) {
                    HumanAccuracyReviewDecision::Accepted => 'human_accuracy_accepted',
                    HumanAccuracyReviewDecision::RevisionRequested => 'human_accuracy_revision_requested',
                    HumanAccuracyReviewDecision::Rejected => 'human_accuracy_rejected',
                    HumanAccuracyReviewDecision::Deferred => 'human_accuracy_deferred',
                })
                ->when(
                    $decision !== HumanAccuracyReviewDecision::Accepted,
                    fn ($c) => $c->push('awaits_human_accuracy_review'),
                )
                ->unique()
                ->values()
                ->all();

            $before = [
                'accuracy_evidence_status' => $previousStatus,
                'human_accuracy_decision' => $previousDecision,
                'human_accuracy_review' => data_get($entry->source_metadata, 'human_accuracy_review'),
                'is_published' => $entry->is_published,
                'answer_key_status' => $entry->answer_key_status?->value,
                'content_rights_status' => $entry->content_rights_status?->value,
            ];

            $entry->fill([
                'accuracy_evidence_status' => $decision->mapsToAccuracyEvidenceStatus(),
                'accuracy_verification_notes' => trim((string) $entry->accuracy_verification_notes)."\n\n[Human accuracy review {$decision->value} by user #{$admin->id}]\n".$notes,
                'accuracy_reviewed_by' => $admin->id,
                'accuracy_reviewed_at' => now(),
                'source_metadata' => $metadata,
                'review_flags' => $flags,
                // Accuracy review never publishes and never promotes.
                'is_published' => false,
            ]);
            $entry->publication_blockers = $entry->computePublicationBlockers();
            $entry->save();

            QuestionImportReviewAudit::query()->create([
                'question_import_entry_id' => $entry->id,
                'user_id' => $admin->id,
                'action' => self::AUDIT_ACTION,
                'before' => $before,
                'after' => [
                    'accuracy_evidence_status' => $entry->accuracy_evidence_status->value,
                    'human_accuracy_decision' => $decision->value,
                    'human_accuracy_review' => $decision === HumanAccuracyReviewDecision::Accepted,
                    'reviewed_content_fingerprint' => $currentFingerprint,
                    'is_published' => false,
                    'answer_key_status' => $entry->answer_key_status->value,
                    'content_rights_status' => $entry->content_rights_status->value,
                    'editorial_approval' => false,
                    'promoted_question_id' => $entry->promoted_question_id,
                    'candidate_id' => data_get($metadata, 'candidate_id'),
                    'confers_editorial_approval' => false,
                    'confers_rights_clearance' => false,
                    'confers_publication' => false,
                    'confers_answer_key_verification' => false,
                ],
                'notes' => $notes,
            ]);

            return $entry->fresh(['translations', 'batch', 'accuracyReviewer']);
        });
    }

    /**
     * @return Collection<int, QuestionImportEntry>
     */
    public function filteredEntries(string $filter = 'pending'): Collection
    {
        $entries = $this->originalEntriesQuery()
            ->with(['translations', 'batch', 'accuracyReviewer'])
            ->orderBy('source_group')
            ->orderBy('original_question_number')
            ->get()
            ->map(fn (QuestionImportEntry $entry): QuestionImportEntry => $this->refreshStaleAccuracyApproval($entry));

        return $entries->filter(function (QuestionImportEntry $entry) use ($filter): bool {
            $decision = HumanAccuracyReviewDecision::tryFrom((string) data_get($entry->source_metadata, 'human_accuracy_decision', ''));
            $needsClarification = data_get($entry->source_metadata, 'research_assessment') === 'supported_with_clarification'
                || in_array('needs_editorial_clarification', $entry->review_flags ?? [], true);

            return match ($filter) {
                'pending' => ! $this->humanAccuracyAcceptedForCurrentVersion($entry)
                    && $decision !== HumanAccuracyReviewDecision::Rejected,
                'needs_clarification' => $needsClarification,
                'accepted' => $this->humanAccuracyAcceptedForCurrentVersion($entry),
                'revision_requested' => $decision === HumanAccuracyReviewDecision::RevisionRequested,
                'rejected_deferred' => in_array($decision, [
                    HumanAccuracyReviewDecision::Rejected,
                    HumanAccuracyReviewDecision::Deferred,
                ], true),
                'all' => true,
                default => throw new InvalidArgumentException('Unknown pilot review filter: '.$filter),
            };
        })->values();
    }
}
