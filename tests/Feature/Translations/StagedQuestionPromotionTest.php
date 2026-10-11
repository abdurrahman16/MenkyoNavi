<?php

namespace Tests\Feature\Translations;

use App\Enums\AccuracyEvidenceStatus;
use App\Enums\AnswerKeyReviewStatus;
use App\Enums\ContentRightsReviewStatus;
use App\Enums\FigureReviewStatus;
use App\Enums\QuestionStatus;
use App\Enums\StagedTranslationStatus;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportEntryTranslation;
use App\Models\SourceReference;
use App\Services\Translations\StagedQuestionPromotionService;
use Database\Seeders\ExamTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StagedQuestionPromotionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_promotion_preserves_answer_key_english_source_and_stays_unpublished(): void
    {
        $this->seed(ExamTypeSeeder::class);

        $entry = QuestionImportEntry::factory()->create([
            'english_question' => 'Overtaking is prohibited within 30 meters before a railway crossing.',
            'english_explanation' => 'Prohibited to prevent accidents at railway crossings.',
            'correct_answer' => true,
            'exam_family' => 'karimen',
            'source_group' => 'karimen_test_1',
            'original_question_number' => 20,
            'answer_key_status' => AnswerKeyReviewStatus::Verified,
            'answer_key_reference' => 'Road Traffic Act practice note (placeholder for review)',
            'answer_key_verified_at' => now(),
            'content_rights_status' => ContentRightsReviewStatus::ApprovedForPublicReuse,
            'accuracy_evidence_status' => AccuracyEvidenceStatus::HumanReviewed,
            'accuracy_verification_notes' => 'Human editorial confirmation recorded for promotion test fixture.',
            'figure_review_status' => FigureReviewStatus::NotApplicable,
            'review_flags' => [],
            'editorial_issues' => [],
        ]);

        QuestionImportEntryTranslation::factory()->create([
            'question_import_entry_id' => $entry->id,
            'locale' => 'ja',
            'question_text' => '踏切とその手前30メートル以内では、追い越しをしてはならない。',
            'explanation' => '踏切での事故を防ぐために禁止されている。',
            'status' => StagedTranslationStatus::ReadyForPublication,
            'reviewed_at' => now(),
        ]);

        QuestionImportEntryTranslation::factory()->create([
            'question_import_entry_id' => $entry->id,
            'locale' => 'en',
            'question_text' => $entry->english_question,
            'explanation' => $entry->english_explanation,
            'status' => StagedTranslationStatus::Draft,
        ]);

        $result = app(StagedQuestionPromotionService::class)->promoteToDraft($entry->fresh(['translations']));

        $this->assertTrue($result['promoted']);
        $question = Question::query()->with(['translations', 'examTypes', 'sourceReference'])->findOrFail($result['question_id']);

        $this->assertSame(QuestionStatus::Draft, $question->status);
        $this->assertFalse($question->is_demo);
        $this->assertTrue($question->correct_answer);
        $this->assertSame('踏切とその手前30メートル以内では、追い越しをしてはならない。', $question->japanese_text);
        $this->assertSame($entry->english_question, $question->translationFor('en')?->question_text);
        $this->assertNotNull($question->sourceReference);
        $this->assertTrue($question->examTypes->contains(fn (ExamType $type): bool => $type->slug === 'karimen'));

        $entry->refresh();
        $this->assertFalse($entry->is_published);
        $this->assertSame($question->id, $entry->promoted_question_id);

        $second = app(StagedQuestionPromotionService::class)->promoteToDraft($entry->fresh(['translations']));
        $this->assertTrue($second['promoted']);
        $this->assertFalse($second['created']);
        $this->assertSame(1, Question::query()->where('is_demo', false)->count());
    }

    #[Test]
    public function test_publication_blockers_prevent_promotion_and_unreviewed_questions_stay_out_of_published_pool(): void
    {
        $entry = QuestionImportEntry::factory()->create([
            'answer_key_status' => AnswerKeyReviewStatus::VerificationPending,
            'content_rights_status' => ContentRightsReviewStatus::Unclear,
            'correct_answer' => false,
        ]);

        QuestionImportEntryTranslation::factory()->create([
            'question_import_entry_id' => $entry->id,
            'locale' => 'ja',
            'status' => StagedTranslationStatus::Draft,
        ]);

        QuestionImportEntryTranslation::factory()->create([
            'question_import_entry_id' => $entry->id,
            'locale' => 'en',
            'question_text' => $entry->english_question,
            'explanation' => $entry->english_explanation,
            'status' => StagedTranslationStatus::Draft,
        ]);

        $blockers = $entry->fresh(['translations'])->computePublicationBlockers();
        $this->assertContains('content_rights_unclear', $blockers);
        $this->assertContains('answer_key_verification_pending', $blockers);
        $this->assertContains('accuracy_evidence_not_checked', $blockers);
        $this->assertContains('japanese_translation_unreviewed', $blockers);

        $result = app(StagedQuestionPromotionService::class)->promoteToDraft($entry->fresh(['translations']));
        $this->assertFalse($result['promoted']);
        $this->assertDatabaseCount('questions', 0);
        $this->assertSame(0, Question::query()->where('status', QuestionStatus::Published)->count());
        $this->assertSame(0, SourceReference::query()->count());
    }

    #[Test]
    public function test_does_not_mutate_existing_demo_questions(): void
    {
        $demo = Question::factory()->demo()->create([
            'japanese_text' => 'デモ問題',
        ]);

        $entry = QuestionImportEntry::factory()->create([
            'promoted_question_id' => $demo->id,
            'answer_key_status' => AnswerKeyReviewStatus::Verified,
            'content_rights_status' => ContentRightsReviewStatus::ApprovedForPublicReuse,
            'accuracy_evidence_status' => AccuracyEvidenceStatus::HumanReviewed,
            'accuracy_verification_notes' => 'Fixture for demo-protection test.',
            'figure_review_status' => FigureReviewStatus::NotApplicable,
            'review_flags' => [],
            'editorial_issues' => [],
        ]);

        QuestionImportEntryTranslation::factory()->create([
            'question_import_entry_id' => $entry->id,
            'locale' => 'ja',
            'status' => StagedTranslationStatus::ReadyForPublication,
            'reviewed_at' => now(),
        ]);
        QuestionImportEntryTranslation::factory()->create([
            'question_import_entry_id' => $entry->id,
            'locale' => 'en',
            'question_text' => $entry->english_question,
            'explanation' => $entry->english_explanation,
        ]);

        $this->expectException(\RuntimeException::class);
        app(StagedQuestionPromotionService::class)->promoteToDraft($entry->fresh(['translations']));
    }
}
