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
 * Stages MenkyoNavi first-party Japanese Karimen pilot candidates.
 *
 * Never publishes, never auto-approves, never mutates imported HTML collections.
 */
class OriginalKarimenPilotStagingService
{
    public const COLLECTION_KEY = 'menkyo_original_karimen_pilot_001';

    public const SOURCE_GROUP = 'menkyo_original_karimen_pilot_001';

    public const CATALOG_RELATIVE_PATH = 'data/original_karimen_pilot_001.php';

    /**
     * @return array{
     *     collection_key: string,
     *     source_group: string,
     *     exam_family: string,
     *     drafting_origin: string,
     *     candidates: list<array<string, mixed>>,
     *     withheld: list<array<string, mixed>>
     * }
     */
    public function catalog(): array
    {
        $path = database_path(self::CATALOG_RELATIVE_PATH);

        if (! is_file($path)) {
            throw new RuntimeException('Original Karimen pilot catalog is missing: '.$path);
        }

        /** @var array<string, mixed> $catalog */
        $catalog = require $path;

        if (($catalog['collection_key'] ?? null) !== self::COLLECTION_KEY) {
            throw new RuntimeException('Pilot catalog collection_key mismatch.');
        }

        if (! is_array($catalog['candidates'] ?? null) || $catalog['candidates'] === []) {
            throw new RuntimeException('Pilot catalog has no candidates.');
        }

        return $catalog;
    }

    /**
     * @return array{
     *     dry_run: bool,
     *     collection_key: string,
     *     candidate_count: int,
     *     created_count: int,
     *     updated_count: int,
     *     skipped_protected_count: int,
     *     batch_id: int|null,
     *     entry_ids: list<int>,
     *     withheld_count: int
     * }
     */
    public function stage(bool $dryRun = true): array
    {
        $catalog = $this->catalog();
        $candidates = $catalog['candidates'];
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $entryIds = [];
        $batchId = null;

        $apply = function () use (
            $catalog,
            $candidates,
            $dryRun,
            &$created,
            &$updated,
            &$skipped,
            &$entryIds,
            &$batchId,
        ): void {
            $checksum = hash('sha256', json_encode($candidates, JSON_UNESCAPED_UNICODE));

            $batch = QuestionImportBatch::query()->firstOrNew([
                'source_key' => self::COLLECTION_KEY,
            ]);

            $batch->fill([
                'source_filename' => 'original_karimen_pilot_001.php',
                'source_relative_path' => 'database/'.self::CATALOG_RELATIVE_PATH,
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
                    'origin' => 'menkyo_original_japanese_pilot',
                    'drafting_origin' => $catalog['drafting_origin'],
                    'publication' => 'unpublished',
                    'human_accuracy_review' => 'pending',
                    'human_editorial_approval' => 'pending',
                    'rights_assessment' => 'pending_human_confirmation',
                    'withheld_count' => count($catalog['withheld'] ?? []),
                ],
                'imported_at' => $batch->imported_at ?? now(),
                'claimed_creator' => 'MenkyoNavi editorial (AI-assisted independent drafting)',
                'origin_url' => null,
                'acquisition_notes' => $catalog['drafting_origin'],
                'license_notes' => 'Original MenkyoNavi draft wording citing public statutes; not cleared for publication without human rights/editorial review.',
                'permission_notes' => null,
                'reuse_scope_notes' => 'Private editorial staging only until human accuracy, editorial, and rights approvals.',
                'figure_rights_notes' => 'Text-only pilot; no figures.',
                'rights_evidence' => [
                    'creation_method' => 'ai_assisted_independent_drafting',
                    'not_from_imported_html_banks' => true,
                    'statutory_citations_only' => true,
                    'human_rights_clearance' => false,
                ],
            ]);

            if ($batch->content_rights_status === null
                || $batch->content_rights_status === BatchContentRightsStatus::NoEvidenceSubmitted
                || $batch->content_rights_status === BatchContentRightsStatus::OriginIdentified) {
                $batch->content_rights_status = BatchContentRightsStatus::OriginIdentified;
            }

            if (! $dryRun) {
                $batch->save();
                $batchId = $batch->id;
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

                $payload = $this->entryPayload($candidate, $catalog['drafting_origin']);

                if ($dryRun) {
                    if ($existing === null) {
                        $created++;
                    } else {
                        $updated++;
                    }

                    continue;
                }

                $payload['question_import_batch_id'] = $batch->id;

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

            if (! $dryRun && $batch->exists) {
                $batch->entry_count = QuestionImportEntry::query()
                    ->where('question_import_batch_id', $batch->id)
                    ->count();
                $batch->save();
                $batchId = $batch->id;
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
            'candidate_count' => count($candidates),
            'created_count' => $created,
            'updated_count' => $updated,
            'skipped_protected_count' => $skipped,
            'batch_id' => $batchId,
            'entry_ids' => $entryIds,
            'withheld_count' => count($catalog['withheld'] ?? []),
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
     * @param  array<string, mixed>  $candidate
     * @return array<string, mixed>
     */
    private function entryPayload(array $candidate, string $draftingOrigin): array
    {
        $questionJa = (string) $candidate['question_ja'];
        $explanationJa = (string) $candidate['explanation_ja'];
        $answer = (bool) $candidate['correct_answer'];
        $englishQuestion = (string) $candidate['english_gloss_question'];
        $englishExplanation = (string) $candidate['english_gloss_explanation'];
        $checksumSeed = self::SOURCE_GROUP.'|'.$candidate['number'].'|'.$questionJa;
        $audit = app(OriginalKarimenPilotAccuracyAudit::class)->forNumber((int) $candidate['number']);
        $researchAssessment = (string) $audit['research_assessment'];

        return [
            'exam_family' => 'karimen',
            'editorial_categories' => null,
            'proposed_exam_category' => null,
            'classification_proposal_source' => 'original_pilot_task_43',
            'study_topics' => [(string) $candidate['subject']],
            'study_topics_approved' => false,
            'classification_approved' => false,
            'editorial_workflow_status' => EditorialWorkflowStatus::NeedsReview,
            'editorial_notes' => implode("\n", [
                'Task 43 original Japanese pilot candidate; Task 44 accuracy audit attached.',
                'Drafting origin: '.$draftingOrigin,
                'Japanese text is authoritative; English fields are non-canonical glosses.',
                'Subject: '.$candidate['subject_ja'].' ('.$candidate['subject'].')',
                'Research assessment: '.$researchAssessment.' (not human approval).',
                'Reviewer notes: '.$candidate['reviewer_notes'],
                'Ambiguity: '.$audit['ambiguity'],
            ]),
            'english_question' => $englishQuestion,
            'correct_answer' => $answer,
            'english_explanation' => $englishExplanation,
            'sign_key' => null,
            'figure_caption' => null,
            'figure_review_status' => FigureReviewStatus::NotApplicable,
            'figure_review_notes' => 'Text-only pilot candidate; no figure required.',
            'source_entry_checksum' => hash('sha256', $checksumSeed),
            'content_fingerprint' => hash('sha256', $questionJa.'|'.($answer ? '1' : '0').'|'.$explanationJa),
            'question_text_fingerprint' => hash('sha256', $questionJa),
            'import_status' => QuestionImportStatus::Staged,
            'media_availability' => QuestionImportMediaStatus::None,
            'duplicate_kind' => QuestionImportDuplicateKind::None,
            'duplicate_of_entry_id' => null,
            'review_flags' => array_values(array_unique(array_filter([
                'original_menkyo_pilot',
                'awaits_human_accuracy_review',
                'awaits_human_editorial_approval',
                'translation_not_ready',
                'task_44_accuracy_audited',
                $researchAssessment === 'supported_with_clarification' ? 'needs_editorial_clarification' : null,
                in_array($researchAssessment, app(OriginalKarimenPilotAccuracyAudit::class)->assessmentsThatBlockAutoPublication(), true)
                    ? 'research_blocks_auto_publication'
                    : null,
            ]))),
            'source_metadata' => [
                'candidate_id' => $candidate['candidate_id'],
                'collection_key' => self::COLLECTION_KEY,
                'authoritative_locale' => 'ja',
                'drafting_origin' => $draftingOrigin,
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
                'exact_provision' => $audit['exact_provision'],
                'ambiguity' => $audit['ambiguity'],
                'proposed_question_ja' => $audit['proposed_question_ja'],
                'human_review_questions' => $audit['human_review_questions'],
                'accuracy_audit_at' => '2026-10-11',
            ],
            'is_published' => false,
            'answer_key_status' => AnswerKeyReviewStatus::VerificationPending,
            'answer_key_notes' => 'Task 44 research assessment: '.$researchAssessment.'. Automated/AI-assisted research only; human accuracy verification still required before any publication.',
            'answer_key_reference' => (string) $audit['exact_provision'],
            'answer_key_verified_at' => null,
            'accuracy_evidence_status' => AccuracyEvidenceStatus::EvidenceCollected,
            'legal_topic' => (string) $candidate['legal_topic'],
            'accuracy_evidence_sources' => [
                [
                    'title' => 'e-Gov primary statute',
                    'label' => 'e-Gov primary statute',
                    'url' => (string) $candidate['source_url'],
                    'section' => (string) $audit['exact_provision'],
                    'checked_at' => '2026-10-11',
                    'relationship' => 'supports',
                    'claim_supported' => (string) $candidate['verification_notes'],
                    'research_assessment' => $researchAssessment,
                ],
            ],
            'accuracy_verification_notes' => implode("\n", [
                (string) $candidate['verification_notes'],
                'Task 44 research assessment: '.$researchAssessment.'.',
                'Evidence summary: '.$audit['evidence_summary'],
                'Ambiguity: '.$audit['ambiguity'],
                'Evidence collected / research audit only; does not confer human_reviewed, answer-key verified, editorial approval, rights clearance, or publication eligibility.',
            ]),
            'accuracy_reviewed_by' => null,
            'accuracy_reviewed_at' => null,
            'proposed_correct_answer' => $answer,
            'proposed_answer_notes' => 'Proposed from primary-source research for editorial review.',
            'content_rights_status' => ContentRightsReviewStatus::Unclear,
            'content_rights_notes' => 'Original draft with statutory citations. Human rights/provenance confirmation still required before public reuse.',
            'content_rights_evidence' => 'AI-assisted independent MenkyoNavi draft citing public statutes; not imported HTML; human rights clearance pending; not claimed as solely human-authored.',
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
                'nuance_note' => 'Original Japanese pilot draft. Awaits human accuracy and editorial review. Not publication-ready.',
                'status' => StagedTranslationStatus::Draft,
                'translator_note' => 'Authoritative Japanese candidate text for menkyo_original_karimen_pilot_001.',
                'quality_flags' => ['awaits_human_review'],
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
                'translator_note' => 'English gloss for admin readability; translation program not complete.',
                'quality_flags' => ['non_canonical_gloss', 'not_native_reviewed'],
                'reviewed_at' => null,
            ],
        );
    }
}
