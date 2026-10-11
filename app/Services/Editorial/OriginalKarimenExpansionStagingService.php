<?php

namespace App\Services\Editorial;

use App\Enums\AccuracyEvidenceStatus;
use App\Enums\AnswerKeyReviewStatus;
use App\Enums\BatchContentRightsStatus;
use App\Enums\ContentRightsReviewStatus;
use App\Enums\EditorialWorkflowStatus;
use App\Enums\FigureReviewStatus;
use App\Enums\QuestionImportDuplicateKind;
use App\Enums\QuestionImportMediaStatus;
use App\Enums\QuestionImportStatus;
use App\Enums\StagedTranslationStatus;
use App\Models\QuestionImportBatch;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportEntryTranslation;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Stages MenkyoNavi first-party Japanese Karimen expansion candidates (11–50).
 *
 * Never publishes, never auto-approves, never mutates imported HTML or pilot collections.
 */
class OriginalKarimenExpansionStagingService
{
    public const COLLECTION_KEY = 'menkyo_original_karimen_expansion_001';

    public const SOURCE_GROUP = 'menkyo_original_karimen_expansion_001';

    public const DATA_DIRECTORY = 'data/original_karimen_expansion_001';

    public const BATCHES = ['A', 'B', 'C', 'D'];

    /**
     * @return array<string, mixed>
     */
    public function manifest(): array
    {
        $path = database_path(self::DATA_DIRECTORY.'/manifest.php');

        if (! is_file($path)) {
            throw new RuntimeException('Expansion manifest missing: '.$path);
        }

        /** @var array<string, mixed> $manifest */
        $manifest = require $path;

        if (($manifest['collection_key'] ?? null) !== self::COLLECTION_KEY) {
            throw new RuntimeException('Expansion manifest collection_key mismatch.');
        }

        return $manifest;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function candidates(?string $batch = null): array
    {
        $manifest = $this->manifest();
        $batches = self::BATCHES;

        if ($batch !== null) {
            $batch = strtoupper($batch);
            if (! in_array($batch, $batches, true)) {
                throw new RuntimeException('Unknown expansion batch: '.$batch);
            }
            $batches = [$batch];
        }

        $candidates = [];

        foreach ($batches as $letter) {
            $file = (string) ($manifest['batch_files'][$letter] ?? 'batch_'.strtolower($letter).'.php');
            $path = database_path(self::DATA_DIRECTORY.'/'.$file);

            if (! is_file($path)) {
                throw new RuntimeException("Expansion batch file missing: {$path}");
            }

            /** @var list<array<string, mixed>> $rows */
            $rows = require $path;

            foreach ($rows as $row) {
                $this->assertCandidateShape($row);
                $candidates[] = $row;
            }
        }

        usort($candidates, fn (array $a, array $b): int => ((int) $a['number']) <=> ((int) $b['number']));

        return $candidates;
    }

    /**
     * @return array{
     *     dry_run: bool,
     *     collection_key: string,
     *     batch_filter: string|null,
     *     candidate_count: int,
     *     created_count: int,
     *     updated_count: int,
     *     skipped_protected_count: int,
     *     batch_id: int|null,
     *     entry_ids: list<int>,
     *     supported_as_written_count: int,
     *     supported_with_clarification_count: int
     * }
     */
    public function stage(bool $dryRun = true, ?string $batch = null): array
    {
        $manifest = $this->manifest();
        $candidates = $this->candidates($batch);
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $entryIds = [];
        $batchId = null;
        $supported = 0;
        $clarification = 0;

        foreach ($candidates as $candidate) {
            $assessment = (string) $candidate['research_assessment'];
            if ($assessment === 'supported_as_written') {
                $supported++;
            } elseif ($assessment === 'supported_with_clarification') {
                $clarification++;
            }
        }

        $apply = function () use (
            $manifest,
            $candidates,
            $dryRun,
            &$created,
            &$updated,
            &$skipped,
            &$entryIds,
            &$batchId,
        ): void {
            $checksum = hash('sha256', json_encode($candidates, JSON_UNESCAPED_UNICODE));

            $batchModel = QuestionImportBatch::query()->firstOrNew([
                'source_key' => self::COLLECTION_KEY,
            ]);

            $batchModel->fill([
                'source_filename' => 'original_karimen_expansion_001/',
                'source_relative_path' => 'database/'.self::DATA_DIRECTORY,
                'file_checksum' => $checksum,
                'source_locale' => 'ja',
                'status' => QuestionImportStatus::Staged,
                'entry_count' => count($candidates),
                'parse_error_count' => 0,
                'duplicate_across_sets_count' => 0,
                'same_wording_different_answer_count' => 0,
                'flagged_for_review_count' => 0,
                'media_review_count' => 0,
                'group_counts' => [self::SOURCE_GROUP => count($candidates)],
                'parse_errors' => [],
                'summary' => [
                    'collection' => self::COLLECTION_KEY,
                    'origin' => 'menkyo_original_japanese_expansion',
                    'drafting_origin' => $manifest['drafting_origin'],
                    'publication' => 'unpublished',
                    'human_accuracy_review' => 'pending',
                    'human_editorial_approval' => 'pending',
                    'rights_assessment' => 'pending_human_confirmation',
                    'batches' => self::BATCHES,
                ],
                'imported_at' => $batchModel->imported_at ?? now(),
                'claimed_creator' => 'MenkyoNavi editorial (AI-assisted independent drafting)',
                'origin_url' => null,
                'acquisition_notes' => (string) $manifest['drafting_origin'],
                'license_notes' => 'Original MenkyoNavi draft wording citing public statutes; not cleared for publication without human rights/editorial review.',
                'permission_notes' => null,
                'reuse_scope_notes' => 'Private editorial staging only until human accuracy, editorial, and rights approvals.',
                'figure_rights_notes' => 'Text-only expansion; no figures.',
                'rights_evidence' => [
                    'creation_method' => 'ai_assisted_independent_drafting',
                    'not_from_imported_html_banks' => true,
                    'statutory_citations_only' => true,
                    'human_rights_clearance' => false,
                    'task' => 48,
                ],
            ]);

            if ($batchModel->content_rights_status === null
                || $batchModel->content_rights_status === BatchContentRightsStatus::NoEvidenceSubmitted
                || $batchModel->content_rights_status === BatchContentRightsStatus::OriginIdentified) {
                $batchModel->content_rights_status = BatchContentRightsStatus::OriginIdentified;
            }

            if (! $dryRun) {
                $batchModel->save();
                $batchId = $batchModel->id;
            }

            foreach ($candidates as $candidate) {
                $number = (int) $candidate['number'];
                $existing = QuestionImportEntry::query()
                    ->where('source_group', self::SOURCE_GROUP)
                    ->where('original_question_number', $number)
                    ->first();

                if ($existing !== null && $this->isHumanProtected($existing)) {
                    $skipped++;
                    $entryIds[] = $existing->id;

                    continue;
                }

                $payload = $this->entryPayload($candidate, (string) $manifest['drafting_origin']);

                if ($dryRun) {
                    if ($existing === null) {
                        $created++;
                    } else {
                        $updated++;
                    }

                    continue;
                }

                $payload['question_import_batch_id'] = $batchModel->id;

                $entry = $existing ?? new QuestionImportEntry([
                    'source_group' => self::SOURCE_GROUP,
                    'original_question_number' => $number,
                ]);
                $entry->fill($payload);
                $entry->source_group = self::SOURCE_GROUP;
                $entry->original_question_number = $number;
                $entry->save();

                $entry->publication_blockers = $entry->computePublicationBlockers();
                $entry->save();

                $this->syncTranslations($entry, $candidate);

                $entry->refresh();
                $entry->publication_blockers = $entry->computePublicationBlockers();
                $entry->save();

                $existing === null ? $created++ : $updated++;
                $entryIds[] = $entry->id;
            }

            if (! $dryRun && $batchModel->exists) {
                $batchModel->entry_count = QuestionImportEntry::query()
                    ->where('question_import_batch_id', $batchModel->id)
                    ->count();
                $batchModel->file_checksum = hash(
                    'sha256',
                    json_encode($this->candidates(), JSON_UNESCAPED_UNICODE),
                );
                $batchModel->save();
                $batchId = $batchModel->id;
            }
        };

        if ($dryRun) {
            $apply();
        } else {
            DB::transaction($apply);
        }

        return [
            'dry_run' => $dryRun,
            'collection_key' => self::COLLECTION_KEY,
            'batch_filter' => $batch === null ? null : strtoupper($batch),
            'candidate_count' => count($candidates),
            'created_count' => $created,
            'updated_count' => $updated,
            'skipped_protected_count' => $skipped,
            'batch_id' => $batchId,
            'entry_ids' => $entryIds,
            'supported_as_written_count' => $supported,
            'supported_with_clarification_count' => $clarification,
        ];
    }

    public function isHumanProtected(QuestionImportEntry $entry): bool
    {
        if ($entry->answer_key_status === AnswerKeyReviewStatus::Verified) {
            return true;
        }

        if ($entry->accuracy_evidence_status === AccuracyEvidenceStatus::HumanReviewed) {
            return true;
        }

        if ($entry->content_rights_status === ContentRightsReviewStatus::ApprovedForPublicReuse) {
            return true;
        }

        if ($entry->accuracy_reviewed_at !== null || $entry->content_rights_reviewed_at !== null) {
            return true;
        }

        if ($entry->promoted_question_id !== null) {
            return true;
        }

        return false;
    }

    /**
     * Limited read-only similarity scan against other source groups.
     *
     * @return list<array{candidate_id: string, overlap_type: string, other_stable_id: string}>
     */
    public function similarityFlags(): array
    {
        $flags = [];

        foreach ($this->candidates() as $candidate) {
            $questionJa = trim((string) $candidate['question_ja']);
            if ($questionJa === '') {
                continue;
            }

            $exactTranslation = QuestionImportEntryTranslation::query()
                ->where('locale', 'ja')
                ->where('question_text', $questionJa)
                ->whereHas('entry', function ($query): void {
                    $query->where('source_group', '!=', self::SOURCE_GROUP);
                })
                ->with('entry:id,source_group,original_question_number')
                ->first();

            if ($exactTranslation !== null && $exactTranslation->entry !== null) {
                $flags[] = [
                    'candidate_id' => (string) $candidate['candidate_id'],
                    'overlap_type' => 'exact_japanese_translation_match',
                    'other_stable_id' => $exactTranslation->entry->stableSourceId(),
                ];
            }
        }

        return $flags;
    }

    /**
     * @param  array<string, mixed>  $candidate
     */
    private function assertCandidateShape(array $candidate): void
    {
        foreach ([
            'candidate_id', 'number', 'batch', 'question_ja', 'explanation_ja',
            'source_url', 'exact_provision', 'research_assessment', 'legal_topic',
        ] as $key) {
            if (! array_key_exists($key, $candidate) || $candidate[$key] === '' || $candidate[$key] === null) {
                throw new RuntimeException("Expansion candidate missing [{$key}].");
            }
        }

        if (! is_bool($candidate['correct_answer'])) {
            throw new RuntimeException('Expansion candidate correct_answer must be boolean.');
        }

        if (! in_array((string) $candidate['research_assessment'], [
            'supported_as_written',
            'supported_with_clarification',
            'ambiguous_needs_revision',
            'evidence_insufficient',
            'potentially_incorrect',
            'legal_effective_date_unresolved',
        ], true)) {
            throw new RuntimeException('Expansion candidate has unknown research_assessment.');
        }
    }

    /**
     * @param  array<string, mixed>  $candidate
     * @return array<string, mixed>
     */
    private function entryPayload(array $candidate, string $draftingOrigin): array
    {
        $questionJa = (string) $candidate['question_ja'];
        $explanationJa = (string) $candidate['explanation_ja'];
        $answer = (bool) $candidate['correct_answer'];
        $researchAssessment = (string) $candidate['research_assessment'];
        $checksumSeed = self::SOURCE_GROUP.'|'.$candidate['number'].'|'.$questionJa;

        return [
            'exam_family' => 'karimen',
            'editorial_categories' => null,
            'proposed_exam_category' => null,
            'classification_proposal_source' => 'original_expansion_task_48',
            'study_topics' => [(string) $candidate['subject']],
            'study_topics_approved' => false,
            'classification_approved' => false,
            'editorial_workflow_status' => EditorialWorkflowStatus::NeedsReview,
            'editorial_notes' => implode("\n", [
                'Task 48 original Japanese expansion candidate.',
                'Batch: '.$candidate['batch'],
                'Drafting origin: '.$draftingOrigin,
                'Japanese text is authoritative; English fields are non-canonical glosses.',
                'Subject: '.$candidate['subject_ja'].' ('.$candidate['subject'].')',
                'Research assessment: '.$researchAssessment.' (not human approval).',
                'Ambiguity: '.$candidate['ambiguity'],
            ]),
            'english_question' => (string) $candidate['english_gloss_question'],
            'correct_answer' => $answer,
            'english_explanation' => (string) $candidate['english_gloss_explanation'],
            'sign_key' => null,
            'figure_caption' => null,
            'figure_review_status' => FigureReviewStatus::NotApplicable,
            'figure_review_notes' => 'Text-only expansion candidate; no figure required.',
            'source_entry_checksum' => hash('sha256', $checksumSeed),
            'content_fingerprint' => hash('sha256', $questionJa.'|'.($answer ? '1' : '0').'|'.$explanationJa),
            'question_text_fingerprint' => hash('sha256', $questionJa),
            'import_status' => QuestionImportStatus::Staged,
            'media_availability' => QuestionImportMediaStatus::None,
            'duplicate_kind' => QuestionImportDuplicateKind::None,
            'duplicate_of_entry_id' => null,
            'review_flags' => array_values(array_unique(array_filter([
                'original_menkyo_expansion',
                'awaits_human_accuracy_review',
                'awaits_human_editorial_approval',
                'translation_not_ready',
                'task_48_expansion',
                'batch_'.strtolower((string) $candidate['batch']),
                $researchAssessment === 'supported_with_clarification' ? 'needs_editorial_clarification' : null,
            ]))),
            'source_metadata' => [
                'candidate_id' => $candidate['candidate_id'],
                'collection_key' => self::COLLECTION_KEY,
                'expansion_batch' => $candidate['batch'],
                'topic_bucket' => $candidate['topic_bucket'] ?? null,
                'authoritative_locale' => 'ja',
                'drafting_origin' => $draftingOrigin,
                'drafting_method' => $candidate['drafting_method'] ?? 'ai_assisted_independent_drafting_task_48',
                'creation_method' => 'ai_assisted_independent_drafting',
                'intended_use' => 'original_japanese_provisional_license_learning_candidate',
                'question_format' => 'true_false',
                'publication_eligible' => false,
                'human_accuracy_review' => false,
                'human_editorial_approval' => false,
                'translation_reviewed' => false,
                'public_exam_eligible' => false,
                'subject' => $candidate['subject'],
                'subject_ja' => $candidate['subject_ja'],
                'source_section' => $candidate['source_section'],
                'source_url' => $candidate['source_url'],
                'research_assessment' => $researchAssessment,
                'research_assessment_confers_human_approval' => false,
                'exact_provision' => $candidate['exact_provision'],
                'statute_excerpt_summary' => $candidate['statute_excerpt_summary'],
                'exceptions' => $candidate['exceptions'],
                'evidence_summary' => $candidate['evidence_summary'],
                'ambiguity' => $candidate['ambiguity'],
                'human_review_questions' => $candidate['human_review_questions'],
                'source_verification_status' => $candidate['source_verification_status'] ?? null,
                'accuracy_audit_at' => '2026-10-11',
            ],
            'is_published' => false,
            'answer_key_status' => AnswerKeyReviewStatus::VerificationPending,
            'answer_key_notes' => 'Task 48 research assessment: '.$researchAssessment.'. Research only; human accuracy verification still required.',
            'answer_key_reference' => (string) $candidate['exact_provision'],
            'answer_key_verified_at' => null,
            'accuracy_evidence_status' => AccuracyEvidenceStatus::EvidenceCollected,
            'legal_topic' => (string) $candidate['legal_topic'],
            'accuracy_evidence_sources' => [
                [
                    'title' => 'e-Gov primary statute',
                    'label' => 'e-Gov primary statute',
                    'url' => (string) $candidate['source_url'],
                    'section' => (string) $candidate['exact_provision'],
                    'checked_at' => '2026-10-11',
                    'relationship' => 'supports',
                    'claim_supported' => (string) $candidate['verification_notes'],
                    'research_assessment' => $researchAssessment,
                ],
            ],
            'accuracy_verification_notes' => implode("\n", [
                (string) $candidate['verification_notes'],
                'Evidence summary: '.$candidate['evidence_summary'],
                'Ambiguity: '.$candidate['ambiguity'],
                'Evidence collected / research only; does not confer human approval, rights clearance, or publication.',
            ]),
            'accuracy_reviewed_by' => null,
            'accuracy_reviewed_at' => null,
            'proposed_correct_answer' => $answer,
            'proposed_answer_notes' => 'Proposed from primary-source research for editorial review.',
            'content_rights_status' => ContentRightsReviewStatus::Unclear,
            'content_rights_notes' => 'Original draft with statutory citations. Human rights/provenance confirmation still required.',
            'content_rights_evidence' => 'AI-assisted independent MenkyoNavi draft citing public statutes; not imported HTML; human rights clearance pending.',
            'content_rights_reviewed_by' => null,
            'content_rights_reviewed_at' => null,
            'editorial_issues' => [],
            'publication_blockers' => [],
            'promoted_question_id' => null,
            'promoted_at' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $candidate
     */
    private function syncTranslations(QuestionImportEntry $entry, array $candidate): void
    {
        QuestionImportEntryTranslation::query()->updateOrCreate(
            [
                'question_import_entry_id' => $entry->id,
                'locale' => 'ja',
            ],
            [
                'question_text' => (string) $candidate['question_ja'],
                'explanation' => (string) $candidate['explanation_ja'],
                'figure_caption' => null,
                'nuance_note' => 'Original Japanese expansion draft. Awaits human accuracy and editorial review. Not publication-ready.',
                'status' => StagedTranslationStatus::Draft,
                'translator_note' => 'Authoritative Japanese candidate text for menkyo_original_karimen_expansion_001.',
                'quality_flags' => ['awaits_human_review', 'expansion_batch_'.strtolower((string) $candidate['batch'])],
                'reviewed_at' => null,
            ],
        );

        QuestionImportEntryTranslation::query()->updateOrCreate(
            [
                'question_import_entry_id' => $entry->id,
                'locale' => 'en',
            ],
            [
                'question_text' => (string) $candidate['english_gloss_question'],
                'explanation' => (string) $candidate['english_gloss_explanation'],
                'figure_caption' => null,
                'nuance_note' => 'Non-canonical English gloss only. Do not treat as localized exam content.',
                'status' => StagedTranslationStatus::Draft,
                'translator_note' => 'English gloss for admin readability; Task 48 does not generate seven-locale drafts.',
                'quality_flags' => ['non_canonical_gloss', 'not_native_reviewed'],
                'reviewed_at' => null,
            ],
        );
    }
}
