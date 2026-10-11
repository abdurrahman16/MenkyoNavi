<?php

namespace Database\Factories;

use App\Enums\AccuracyEvidenceStatus;
use App\Enums\AnswerKeyReviewStatus;
use App\Enums\ContentRightsReviewStatus;
use App\Enums\EditorialWorkflowStatus;
use App\Enums\FigureReviewStatus;
use App\Enums\QuestionImportDuplicateKind;
use App\Enums\QuestionImportMediaStatus;
use App\Enums\QuestionImportStatus;
use App\Models\QuestionImportBatch;
use App\Models\QuestionImportEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionImportEntry>
 */
class QuestionImportEntryFactory extends Factory
{
    protected $model = QuestionImportEntry::class;

    public function definition(): array
    {
        $question = fake()->sentence(12);
        $explanation = fake()->sentence(18);
        $answer = fake()->boolean();
        $group = 'karimen_test_1';
        $number = fake()->unique()->numberBetween(1, 50);
        $checksumSeed = $group.'|'.$number.'|'.$question;

        return [
            'question_import_batch_id' => QuestionImportBatch::factory(),
            'source_group' => $group,
            'exam_family' => 'karimen',
            'editorial_categories' => null,
            'proposed_exam_category' => null,
            'classification_proposal_source' => null,
            'study_topics' => null,
            'study_topics_approved' => false,
            'classification_approved' => false,
            'editorial_workflow_status' => EditorialWorkflowStatus::NeedsReview,
            'editorial_notes' => null,
            'original_question_number' => $number,
            'english_question' => $question,
            'correct_answer' => $answer,
            'english_explanation' => $explanation,
            'sign_key' => null,
            'figure_caption' => null,
            'figure_review_status' => FigureReviewStatus::NotApplicable,
            'figure_review_notes' => null,
            'source_entry_checksum' => hash('sha256', $checksumSeed),
            'content_fingerprint' => hash('sha256', $question.'|'.($answer ? '1' : '0').'|'.$explanation),
            'question_text_fingerprint' => hash('sha256', $question),
            'import_status' => QuestionImportStatus::Staged,
            'media_availability' => QuestionImportMediaStatus::None,
            'duplicate_kind' => QuestionImportDuplicateKind::None,
            'duplicate_of_entry_id' => null,
            'review_flags' => [],
            'source_metadata' => null,
            'is_published' => false,
            'answer_key_status' => AnswerKeyReviewStatus::VerificationPending,
            'answer_key_notes' => null,
            'answer_key_reference' => null,
            'answer_key_verified_at' => null,
            'accuracy_evidence_status' => AccuracyEvidenceStatus::NotChecked,
            'legal_topic' => null,
            'accuracy_evidence_sources' => null,
            'accuracy_verification_notes' => null,
            'accuracy_reviewed_by' => null,
            'accuracy_reviewed_at' => null,
            'content_rights_status' => ContentRightsReviewStatus::Unclear,
            'content_rights_notes' => null,
            'editorial_issues' => [],
            'publication_blockers' => [],
            'promoted_question_id' => null,
            'promoted_at' => null,
        ];
    }
}
