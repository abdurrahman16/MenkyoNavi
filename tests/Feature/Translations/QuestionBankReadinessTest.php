<?php

namespace Tests\Feature\Translations;

use App\Enums\AnswerKeyReviewStatus;
use App\Enums\ContentRightsReviewStatus;
use App\Enums\StagedTranslationStatus;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportEntryTranslation;
use App\Services\Translations\QuestionBankReadinessReporter;
use Database\Seeders\ExamTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QuestionBankReadinessTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_readiness_report_distinguishes_staged_draft_reviewed_and_blocks_publication_claims(): void
    {
        $this->seed(ExamTypeSeeder::class);

        $staged = QuestionImportEntry::factory()->create([
            'exam_family' => 'honmen',
            'source_group' => 'honmen_dl_1',
            'answer_key_status' => AnswerKeyReviewStatus::VerificationPending,
            'content_rights_status' => ContentRightsReviewStatus::Unclear,
        ]);

        QuestionImportEntryTranslation::factory()->create([
            'question_import_entry_id' => $staged->id,
            'locale' => 'ja',
            'status' => StagedTranslationStatus::Draft,
        ]);

        QuestionImportEntryTranslation::factory()->reviewed()->create([
            'question_import_entry_id' => $staged->id,
            'locale' => 'en',
            'question_text' => $staged->english_question,
            'explanation' => $staged->english_explanation,
        ]);

        $report = app(QuestionBankReadinessReporter::class)->report();

        $this->assertSame(1, $report['staged_questions_total']);
        $this->assertSame(1, $report['draft_translations_by_locale']['ja']);
        $this->assertSame(1, $report['reviewed_translations_by_locale']['en']);
        $this->assertSame(0, $report['publication_ready_questions']);
        $this->assertSame(1, $report['questions_blocked_from_publishing']);
        $this->assertFalse($report['question_bank_ready_for_public_exams']);
        $this->assertFalse($report['full_mock_requirements_met']);
        $this->assertStringContainsString('does not mean', $report['disclaimer']);

        $exit = Artisan::call('menkyo:question-readiness');
        $output = Artisan::output();
        $this->assertSame(0, $exit);
        $this->assertStringContainsString('question-bank readiness', $output);
        $this->assertStringContainsString('publication-ready', mb_strtolower($output));
        $this->assertFalse($report['question_bank_ready_for_public_exams']);
    }
}
