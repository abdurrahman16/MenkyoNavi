<?php

namespace App\Services\Editorial;

use App\Enums\AccuracyEvidenceStatus;
use App\Enums\AnswerKeyReviewStatus;
use App\Enums\BatchContentRightsStatus;
use App\Enums\ContentRightsReviewStatus;
use App\Enums\DuplicatePairDecision;
use App\Enums\EditorialExamCategory;
use App\Enums\EditorialIssueType;
use App\Enums\EditorialStudyTopic;
use App\Enums\EditorialWorkflowStatus;
use App\Enums\FigureReviewStatus;
use App\Enums\QuestionImportDuplicateKind;
use App\Models\QuestionImportBatch;
use App\Models\QuestionImportDuplicateReview;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportReviewAudit;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Mutates editorial review fields only. Never changes original English text or answer keys.
 */
class EditorialReviewService
{
    /**
     * @var list<string>
     */
    private const IMMUTABLE_FIELDS = [
        'english_question',
        'correct_answer',
        'english_explanation',
        'sign_key',
        'figure_caption',
        'source_group',
        'original_question_number',
        'source_entry_checksum',
        'content_fingerprint',
        'question_text_fingerprint',
        'exam_family',
    ];

    /**
     * @param  list<string>  $categoryValues
     */
    public function updateClassification(
        QuestionImportEntry $entry,
        User $admin,
        array $categoryValues,
        bool $approve,
        ?string $notes = null,
    ): QuestionImportEntry {
        $categories = $this->normalizeCategories($categoryValues);

        return $this->mutate($entry, $admin, 'classification_updated', [
            'editorial_categories' => array_map(
                static fn (EditorialExamCategory $category): string => $category->value,
                $categories,
            ),
            'classification_approved' => $approve,
            'classification_approved_by' => $approve ? $admin->id : null,
            'classification_approved_at' => $approve ? now() : null,
            'editorial_workflow_status' => $approve
                ? EditorialWorkflowStatus::Classified->value
                : EditorialWorkflowStatus::InReview->value,
        ], $notes);
    }

    public function updateAnswerKeyReview(
        QuestionImportEntry $entry,
        User $admin,
        AnswerKeyReviewStatus $status,
        ?string $notes,
        ?string $reference,
        ?bool $proposedCorrectAnswer,
        ?string $proposedAnswerNotes,
    ): QuestionImportEntry {
        if ($status === AnswerKeyReviewStatus::Verified && blank($notes) && blank($reference)) {
            throw ValidationException::withMessages([
                'answer_key_notes' => 'Verified answer keys require evidence notes or a reference URL.',
            ]);
        }

        return $this->mutate($entry, $admin, 'answer_key_review_updated', [
            'answer_key_status' => $status->value,
            'answer_key_notes' => $notes,
            'answer_key_reference' => $reference,
            'answer_key_verified_at' => $status === AnswerKeyReviewStatus::Verified ? now() : $entry->answer_key_verified_at,
            'proposed_correct_answer' => $proposedCorrectAnswer,
            'proposed_answer_notes' => $proposedAnswerNotes,
        ], $notes);
    }

    public function updateContentRights(
        QuestionImportEntry $entry,
        User $admin,
        ContentRightsReviewStatus $status,
        ?string $notes,
        ?string $evidence,
    ): QuestionImportEntry {
        if ($status->requiresEvidenceForApproval() && blank($evidence) && blank($notes)) {
            throw ValidationException::withMessages([
                'content_rights_evidence' => 'Supporting evidence is required before treating reuse rights as obtained or approved.',
            ]);
        }

        return $this->mutate($entry, $admin, 'content_rights_review_updated', [
            'content_rights_status' => $status->value,
            'content_rights_notes' => $notes,
            'content_rights_evidence' => $evidence,
            'content_rights_reviewed_by' => $admin->id,
            'content_rights_reviewed_at' => now(),
        ], $notes);
    }

    /**
     * @param  list<array<string, mixed>>|null  $sources
     */
    public function updateAccuracyEvidence(
        QuestionImportEntry $entry,
        User $admin,
        AccuracyEvidenceStatus $status,
        ?string $legalTopic,
        ?array $sources,
        ?string $verificationNotes,
    ): QuestionImportEntry {
        if ($status === AccuracyEvidenceStatus::HumanReviewed && blank($verificationNotes) && blank($sources)) {
            throw ValidationException::withMessages([
                'accuracy_verification_notes' => 'Human-reviewed accuracy status requires verification notes or recorded sources.',
            ]);
        }

        if ($status === AccuracyEvidenceStatus::EvidenceCollected && blank($sources) && blank($verificationNotes)) {
            throw ValidationException::withMessages([
                'accuracy_evidence_sources' => 'Evidence-collected status requires at least one source record or verification notes.',
            ]);
        }

        return $this->mutate($entry, $admin, 'accuracy_evidence_updated', [
            'accuracy_evidence_status' => $status->value,
            'legal_topic' => $legalTopic,
            'accuracy_evidence_sources' => $sources,
            'accuracy_verification_notes' => $verificationNotes,
            'accuracy_reviewed_by' => $admin->id,
            'accuracy_reviewed_at' => now(),
        ], $verificationNotes);
    }

    public function updateFigureReview(
        QuestionImportEntry $entry,
        User $admin,
        FigureReviewStatus $status,
        ?string $notes,
    ): QuestionImportEntry {
        if ($entry->isFigureDependent() && $status === FigureReviewStatus::NotApplicable) {
            throw ValidationException::withMessages([
                'figure_review_status' => 'Figure-dependent questions cannot be marked not_applicable.',
            ]);
        }

        if ($status === FigureReviewStatus::ReviewedSufficient && blank($notes)) {
            throw ValidationException::withMessages([
                'figure_review_notes' => 'Marking a figure as reviewed-sufficient requires review notes.',
            ]);
        }

        return $this->mutate($entry, $admin, 'figure_review_updated', [
            'figure_review_status' => $status->value,
            'figure_review_notes' => $notes,
        ], $notes);
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    public function updateBatchContentRights(
        QuestionImportBatch $batch,
        User $admin,
        BatchContentRightsStatus $status,
        array $fields,
        ?UploadedFile $document = null,
    ): QuestionImportBatch {
        $patch = $this->normalizeBatchRightsFields($fields);

        if ($status === BatchContentRightsStatus::ClearedByAuthorizedHuman) {
            $hasPermissionNotes = filled($patch['permission_notes'] ?? $batch->permission_notes);
            $evidence = $patch['rights_evidence'] ?? $batch->rights_evidence;
            $hasRightsEvidence = is_array($evidence) && $evidence !== [];
            $hasDocument = $document !== null
                || filled($patch['rights_document_path'] ?? $batch->rights_document_path);

            if (! $hasPermissionNotes && ! $hasRightsEvidence && ! $hasDocument) {
                throw ValidationException::withMessages([
                    'batchContentRightsStatus' => 'Cleared status requires permission notes, rights evidence records, or an uploaded permission document.',
                ]);
            }

            $hasAnyNotes = filled($patch['acquisition_notes'] ?? $batch->acquisition_notes)
                || filled($patch['license_notes'] ?? $batch->license_notes)
                || filled($patch['permission_notes'] ?? $batch->permission_notes)
                || filled($patch['reuse_scope_notes'] ?? $batch->reuse_scope_notes)
                || filled($patch['figure_rights_notes'] ?? $batch->figure_rights_notes);

            if (! $hasAnyNotes && ! $hasRightsEvidence && ! $hasDocument) {
                throw ValidationException::withMessages([
                    'batchContentRightsStatus' => 'Cleared status requires supporting notes or evidence before human clearance.',
                ]);
            }
        }

        if ($document !== null) {
            Validator::make(
                ['rights_document' => $document],
                ['rights_document' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg,txt', 'max:5120']],
            )->validate();

            if ($batch->rights_document_path !== null) {
                Storage::disk('local')->delete($batch->rights_document_path);
            }

            $storedName = Str::uuid()->toString().'_'.$document->getClientOriginalName();
            $storedPath = $document->storeAs(
                'private/menkyo-rights/'.$batch->id,
                $storedName,
                'local',
            );

            $patch['rights_document_path'] = $storedPath;
            $patch['rights_document_original_name'] = $document->getClientOriginalName();
        }

        $patch['content_rights_status'] = $status->value;
        $patch['content_rights_reviewed_by'] = $admin->id;
        $patch['content_rights_reviewed_at'] = now();

        return DB::transaction(function () use ($batch, $admin, $patch): QuestionImportBatch {
            $before = $batch->only(array_keys($patch));
            $batch->fill($patch);
            $batch->save();

            $firstEntry = $batch->entries()->orderBy('id')->first();

            if ($firstEntry !== null) {
                QuestionImportReviewAudit::query()->create([
                    'question_import_entry_id' => $firstEntry->id,
                    'user_id' => $admin->id,
                    'action' => 'batch_content_rights_updated',
                    'before' => $before,
                    'after' => $batch->only(array_keys($patch)),
                    'notes' => $patch['permission_notes'] ?? $batch->permission_notes,
                ]);
            }

            return $batch->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    private function normalizeBatchRightsFields(array $fields): array
    {
        $allowed = [
            'claimed_creator',
            'origin_url',
            'acquisition_notes',
            'license_notes',
            'permission_notes',
            'reuse_scope_notes',
            'figure_rights_notes',
            'rights_evidence',
        ];

        $patch = [];

        foreach ($allowed as $key) {
            if (! array_key_exists($key, $fields)) {
                continue;
            }

            $value = $fields[$key];

            if ($key === 'rights_evidence') {
                if ($value === null || $value === '') {
                    $patch[$key] = null;
                } elseif (is_array($value)) {
                    $patch[$key] = $value;
                } else {
                    throw ValidationException::withMessages([
                        'batchRightsEvidenceJson' => 'Rights evidence must be a JSON array.',
                    ]);
                }

                continue;
            }

            $patch[$key] = filled($value) ? (string) $value : null;
        }

        return $patch;
    }

    /**
     * Apply catalog-suggested evidence fields without approving answers or rights.
     */
    public function seedAccuracyEvidenceFromLedger(
        QuestionImportEntry $entry,
        User $admin,
        KarimenTest1EvidenceLedger $ledger,
    ): QuestionImportEntry {
        $patch = $ledger->applyCollectedEvidence($entry);

        return $this->mutate($entry, $admin, 'accuracy_evidence_seeded_from_ledger', $patch, $patch['accuracy_verification_notes'] ?? null);
    }

    /**
     * @param  list<string>  $issueValues
     */
    public function updateEditorialIssues(
        QuestionImportEntry $entry,
        User $admin,
        array $issueValues,
        ?string $editorialNotes,
        EditorialWorkflowStatus $workflowStatus,
    ): QuestionImportEntry {
        $issues = [];

        foreach ($issueValues as $value) {
            $issue = EditorialIssueType::tryFrom((string) $value);

            if ($issue === null) {
                throw new InvalidArgumentException('Unknown editorial issue: '.$value);
            }

            $issues[] = $issue->value;
        }

        return $this->mutate($entry, $admin, 'editorial_issues_updated', [
            'editorial_issues' => array_values(array_unique($issues)),
            'editorial_notes' => $editorialNotes,
            'editorial_workflow_status' => $workflowStatus->value,
        ], $editorialNotes);
    }

    public function recordDuplicateDecision(
        QuestionImportEntry $entry,
        QuestionImportEntry $related,
        User $admin,
        DuplicatePairDecision $decision,
        string $similarityReason,
        ?string $notes = null,
    ): QuestionImportDuplicateReview {
        return DB::transaction(function () use ($entry, $related, $admin, $decision, $similarityReason, $notes): QuestionImportDuplicateReview {
            $review = QuestionImportDuplicateReview::query()->updateOrCreate(
                [
                    'entry_id' => min($entry->id, $related->id),
                    'related_entry_id' => max($entry->id, $related->id),
                ],
                [
                    'similarity_reason' => $similarityReason,
                    'decision' => $decision->value,
                    'reviewed_by' => $admin->id,
                    'notes' => $notes,
                    'reviewed_at' => now(),
                ],
            );

            QuestionImportReviewAudit::query()->create([
                'question_import_entry_id' => $entry->id,
                'user_id' => $admin->id,
                'action' => 'duplicate_pair_reviewed',
                'before' => null,
                'after' => [
                    'related_entry_id' => $related->id,
                    'decision' => $decision->value,
                    'similarity_reason' => $similarityReason,
                ],
                'notes' => $notes,
            ]);

            return $review;
        });
    }

    public function ensureSimilarPairReviewsSeeded(): int
    {
        $created = 0;

        $similarEntries = QuestionImportEntry::query()
            ->with(['batch', 'duplicateOf.batch'])
            ->where('duplicate_kind', QuestionImportDuplicateKind::SimilarWordingNeedsReview)
            ->whereNotNull('duplicate_of_entry_id')
            ->get();

        foreach ($similarEntries as $entry) {
            $related = $entry->duplicateOf;

            if ($related === null) {
                continue;
            }

            $pair = QuestionImportDuplicateReview::query()->firstOrCreate(
                [
                    'entry_id' => min($entry->id, $related->id),
                    'related_entry_id' => max($entry->id, $related->id),
                ],
                [
                    'similarity_reason' => 'similar_wording_needs_review',
                    'decision' => DuplicatePairDecision::NeedsAdditionalReview->value,
                ],
            );

            if ($pair->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * Propose editorial categories from source exam_family without approving them.
     */
    public function proposeClassificationsFromSource(User $admin): int
    {
        return app(ClassificationProposalService::class)
            ->generateExamClassificationProposals($admin)['updated'];
    }

    /**
     * @param  list<int>  $entryIds
     * @return array{updated: int, skipped_approved: int, skipped_missing_proposal: int}
     */
    public function bulkApproveExamClassifications(
        User $admin,
        array $entryIds,
        EditorialExamCategory $category,
        bool $overrideApproved = false,
        ?string $notes = null,
    ): array {
        $stats = ['updated' => 0, 'skipped_approved' => 0, 'skipped_missing_proposal' => 0];

        if ($entryIds === []) {
            return $stats;
        }

        $entries = QuestionImportEntry::query()
            ->whereIn('id', $entryIds)
            ->orderBy('id')
            ->get();

        foreach ($entries as $entry) {
            if ($entry->classification_approved && ! $overrideApproved) {
                $stats['skipped_approved']++;

                continue;
            }

            $proposed = $entry->proposed_exam_category
                ? EditorialExamCategory::tryFrom((string) $entry->proposed_exam_category)
                : app(ClassificationProposalService::class)->proposeExamCategoryForEntry($entry);

            if ($proposed === null || $proposed !== $category) {
                $stats['skipped_missing_proposal']++;

                continue;
            }

            if ($proposed === EditorialExamCategory::Unclassified) {
                $stats['skipped_missing_proposal']++;

                continue;
            }

            $this->updateClassification(
                $entry,
                $admin,
                [$category->value],
                true,
                $notes ?? 'Bulk approved exam classification from proposal source='.($entry->classification_proposal_source ?? 'manual').'.',
            );

            $stats['updated']++;
        }

        return $stats;
    }

    /**
     * @param  list<string>  $topicValues
     */
    public function updateStudyTopics(
        QuestionImportEntry $entry,
        User $admin,
        array $topicValues,
        bool $approve = false,
        ?string $notes = null,
    ): QuestionImportEntry {
        $topics = $this->normalizeStudyTopics($topicValues);

        return $this->mutate($entry, $admin, 'study_topics_updated', [
            'study_topics' => array_map(
                static fn (EditorialStudyTopic $topic): string => $topic->value,
                $topics,
            ),
            'study_topics_approved' => $approve,
        ], $notes ?? 'Study topics assigned. Topic approval does not grant exam eligibility or publication readiness.');
    }

    /**
     * @param  list<int>  $entryIds
     * @param  list<string>  $topicValues
     * @return array{updated: int}
     */
    public function bulkAssignStudyTopics(
        User $admin,
        array $entryIds,
        array $topicValues,
        bool $approve = false,
        ?string $notes = null,
    ): array {
        $updated = 0;

        foreach (QuestionImportEntry::query()->whereIn('id', $entryIds)->orderBy('id')->cursor() as $entry) {
            $this->updateStudyTopics(
                $entry,
                $admin,
                $topicValues,
                $approve,
                $notes ?? 'Study topics assigned. Topic approval does not grant exam eligibility.',
            );

            $updated++;
        }

        return ['updated' => $updated];
    }

    /**
     * @param  list<int>  $entryIds
     * @return array{updated: int}
     */
    public function bulkMarkForAdditionalReview(
        User $admin,
        array $entryIds,
        ?string $notes = null,
    ): array {
        $updated = 0;

        foreach (QuestionImportEntry::query()->whereIn('id', $entryIds)->orderBy('id')->cursor() as $entry) {
            $issues = $entry->editorial_issues ?? [];
            $issues[] = EditorialIssueType::CategoryUncertainty->value;

            $this->updateEditorialIssues(
                $entry,
                $admin,
                array_values(array_unique($issues)),
                trim(($entry->editorial_notes ? $entry->editorial_notes."\n" : '').($notes ?? 'Marked for additional editorial review.')),
                EditorialWorkflowStatus::NeedsReview,
            );

            $updated++;
        }

        return ['updated' => $updated];
    }

    /**
     * @param  list<int>  $entryIds
     * @return array{updated: int}
     */
    public function bulkAppendEditorialNotes(User $admin, array $entryIds, string $notes): array
    {
        $updated = 0;
        $notes = trim($notes);

        if ($notes === '' || $entryIds === []) {
            return ['updated' => 0];
        }

        foreach (QuestionImportEntry::query()->whereIn('id', $entryIds)->orderBy('id')->cursor() as $entry) {
            $combined = trim(($entry->editorial_notes ? $entry->editorial_notes."\n" : '').$notes);

            $this->mutate($entry, $admin, 'editorial_notes_appended', [
                'editorial_notes' => $combined,
            ], $notes);

            $updated++;
        }

        return ['updated' => $updated];
    }

    /**
     * @param  list<string>  $topicValues
     * @return list<EditorialStudyTopic>
     */
    private function normalizeStudyTopics(array $topicValues): array
    {
        if ($topicValues === []) {
            return [EditorialStudyTopic::GeneralTrafficRules];
        }

        $topics = [];

        foreach ($topicValues as $value) {
            $topic = EditorialStudyTopic::tryFrom((string) $value);

            if ($topic === null) {
                throw new InvalidArgumentException('Unknown study topic: '.$value);
            }

            $topics[$topic->value] = $topic;
        }

        return array_values($topics);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function mutate(
        QuestionImportEntry $entry,
        User $admin,
        string $action,
        array $attributes,
        ?string $notes = null,
    ): QuestionImportEntry {
        foreach (self::IMMUTABLE_FIELDS as $field) {
            if (array_key_exists($field, $attributes)) {
                throw new InvalidArgumentException("Refusing to mutate immutable field [{$field}].");
            }
        }

        return DB::transaction(function () use ($entry, $admin, $action, $attributes, $notes): QuestionImportEntry {
            $before = $entry->only(array_keys($attributes));
            $entry->fill($attributes);
            $entry->publication_blockers = $entry->computePublicationBlockers();
            $entry->save();

            QuestionImportReviewAudit::query()->create([
                'question_import_entry_id' => $entry->id,
                'user_id' => $admin->id,
                'action' => $action,
                'before' => $before,
                'after' => $entry->only(array_keys($attributes)),
                'notes' => $notes,
            ]);

            return $entry->refresh();
        });
    }

    /**
     * @param  list<string>  $categoryValues
     * @return list<EditorialExamCategory>
     */
    private function normalizeCategories(array $categoryValues): array
    {
        if ($categoryValues === []) {
            return [EditorialExamCategory::Unclassified];
        }

        $categories = [];

        foreach ($categoryValues as $value) {
            $category = EditorialExamCategory::tryFrom((string) $value);

            if ($category === null) {
                throw new InvalidArgumentException('Unknown editorial category: '.$value);
            }

            $categories[$category->value] = $category;
        }

        return array_values($categories);
    }
}
