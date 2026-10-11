<?php

namespace Tests\Feature\Editorial;

use App\Enums\AnswerKeyReviewStatus;
use App\Enums\ContentRightsReviewStatus;
use App\Enums\ExamAttemptType;
use App\Enums\StagedTranslationStatus;
use App\Livewire\Admin\QuestionBankDashboard;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportEntryTranslation;
use App\Models\User;
use App\Services\Editorial\KarimenTest1AuditService;
use App\Services\Exams\ExamAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Exams\CreatesPracticeExams;
use Tests\TestCase;

class KarimenTest1AuditTest extends TestCase
{
    use CreatesPracticeExams;
    use RefreshDatabase;

    /**
     * @return list<QuestionImportEntry>
     */
    private function seedKarimenTest1Set(int $count = 50): array
    {
        fake()->unique(true);

        $entries = [];

        for ($number = 1; $number <= $count; $number++) {
            $entries[] = QuestionImportEntry::factory()->create([
                'source_group' => 'karimen_test_1',
                'original_question_number' => $number,
                'english_question' => "Karimen test 1 question {$number} must not be rewritten.",
                'english_explanation' => "Explanation {$number} stays immutable.",
                'correct_answer' => $number % 2 === 0,
                'figure_caption' => in_array($number, [2, 28], true) ? "Figure {$number}" : null,
                'sign_key' => in_array($number, [2, 28], true) ? "sign{$number}" : null,
                'review_flags' => in_array($number, [4, 23], true) ? ['ambiguous_terminology'] : [],
                'answer_key_status' => AnswerKeyReviewStatus::VerificationPending,
                'content_rights_status' => ContentRightsReviewStatus::Unclear,
                'is_published' => false,
                'source_entry_checksum' => hash('sha256', 'karimen_test_1|'.$number),
            ]);
        }

        foreach ([1, 2, 28, 29] as $number) {
            $entry = collect($entries)->firstWhere('original_question_number', $number);
            QuestionImportEntryTranslation::factory()->create([
                'question_import_entry_id' => $entry->id,
                'locale' => 'ja',
                'question_text' => "日本語下書き {$number} は変更されない。",
                'explanation' => "解説 {$number}",
                'figure_caption' => $entry->figure_caption ? "図 {$number}" : null,
                'status' => in_array($number, [2, 28], true)
                    ? StagedTranslationStatus::NeedsReview
                    : StagedTranslationStatus::Draft,
            ]);
        }

        return $entries;
    }

    #[Test]
    public function test_audit_selects_exactly_fifty_karimen_test_1_entries_and_preserves_source_immutability(): void
    {
        $this->seedKarimenTest1Set(50);
        fake()->unique(true);
        QuestionImportEntry::factory()->create([
            'source_group' => 'karimen_test_2',
            'original_question_number' => 1,
            'english_question' => 'Other group must not be audited here.',
        ]);

        $before = QuestionImportEntry::query()
            ->where('source_group', 'karimen_test_1')
            ->orderBy('original_question_number')
            ->get(['id', 'english_question', 'english_explanation', 'correct_answer', 'source_entry_checksum', 'review_flags', 'answer_key_status', 'content_rights_status', 'is_published']);

        $audit = app(KarimenTest1AuditService::class)->audit();

        $this->assertSame(50, $audit['readiness']['total_questions']);
        $this->assertSame(50, count($audit['items']));
        $this->assertSame(range(1, 50), collect($audit['items'])->pluck('original_question_number')->all());
        $this->assertFalse(collect($audit['items'])->contains(
            fn (array $item): bool => $item['source_group'] !== 'karimen_test_1'
        ));

        $after = QuestionImportEntry::query()
            ->where('source_group', 'karimen_test_1')
            ->orderBy('original_question_number')
            ->get(['id', 'english_question', 'english_explanation', 'correct_answer', 'source_entry_checksum', 'review_flags', 'answer_key_status', 'content_rights_status', 'is_published']);

        $this->assertSame($before->toArray(), $after->toArray());
        $this->assertSame(0, $audit['readiness']['verified_answer_keys']);
        $this->assertSame(0, $audit['readiness']['rights_cleared_questions']);
        $this->assertSame(0, $audit['readiness']['fully_publication_ready_questions']);
        $this->assertSame(50, $audit['readiness']['questions_blocked_from_publication']);
        $this->assertTrue($audit['disclaimer']['english_source_immutable']);
        $this->assertArrayHasKey('task_16_state', $audit);
        $this->assertSame(2, $audit['format_version']);
        $this->assertArrayHasKey('task_32_state', $audit);
        $this->assertArrayHasKey('batch_rights', $audit);
        $this->assertTrue($audit['disclaimer']['task_32_evidence_collected_is_not_verified']);
        $this->assertNotEmpty($audit['items'][0]['evidence_ledger']);
    }

    #[Test]
    public function test_audit_reports_translation_coverage_review_flags_missing_evidence_and_figures(): void
    {
        $this->seedKarimenTest1Set(50);

        $audit = app(KarimenTest1AuditService::class)->audit();

        $this->assertSame(4, $audit['task_16_state']['japanese_drafts']);
        $this->assertSame(0, $audit['task_16_state']['eight_language_complete']);
        $this->assertFalse($audit['task_16_state']['task_16_complete']);
        $this->assertSame(46, $audit['task_16_state']['missing_by_locale']['ja']);

        $q4 = collect($audit['items'])->firstWhere('original_question_number', 4);
        $this->assertNotNull($q4);
        $this->assertSame(['ambiguous_terminology'], $q4['database_facts']['review_flags']);
        $this->assertTrue($q4['missing_evidence']['answer_key_verified_evidence']);
        $this->assertTrue($q4['missing_evidence']['content_rights_authorization_evidence']);
        $this->assertSame('original_wording_unclear', $q4['automated_review_suggestions']['japanese_finding']);

        $q2 = collect($audit['items'])->firstWhere('original_question_number', 2);
        $this->assertTrue($q2['figure_dependent']);
        $this->assertSame('figure_interpretation_required', $q2['automated_review_suggestions']['japanese_finding']);
        $this->assertNotNull($q2['automated_review_suggestions']['special_queue']);

        $priorityNumbers = collect($audit['priority_review_queue'])->pluck('original_question_number')->all();
        $this->assertContains(4, $priorityNumbers);
        $this->assertContains(23, $priorityNumbers);
        $this->assertContains(2, $priorityNumbers);
    }

    #[Test]
    public function test_private_audit_export_stays_outside_public_and_does_not_publish(): void
    {
        $this->seedKarimenTest1Set(50);
        $service = app(KarimenTest1AuditService::class);
        $audit = $service->audit();

        $jsonPath = storage_path('app/menkyo-exports/test-karimen-audit.json');
        $mdPath = storage_path('app/menkyo-exports/test-karimen-audit.md');
        File::delete([$jsonPath, $mdPath]);

        $paths = $service->writePrivateReports($audit, $jsonPath, $mdPath);

        $this->assertFileExists($paths['json_path']);
        $this->assertFileExists($paths['markdown_path']);
        $this->assertStringContainsString('menkyo-exports', $paths['json_path']);
        $this->assertStringNotContainsString(str_replace('\\', '/', public_path()), str_replace('\\', '/', $paths['json_path']));
        $this->assertStringContainsString('human translation review', File::get($mdPath));

        $this->assertSame(0, QuestionImportEntry::query()->where('is_published', true)->count());
        $this->assertSame(
            50,
            QuestionImportEntry::query()->where('answer_key_status', AnswerKeyReviewStatus::VerificationPending)->count()
        );
        $this->assertSame(
            50,
            QuestionImportEntry::query()->where('content_rights_status', ContentRightsReviewStatus::Unclear)->count()
        );

        $this->expectException(\RuntimeException::class);
        $service->writePrivateReports($audit, public_path('karimen-audit.json'), $mdPath);
    }

    #[Test]
    public function test_admin_can_export_karimen_test_1_audit_and_exam_scoring_still_works(): void
    {
        $this->seedKarimenTest1Set(50);
        $admin = User::factory()->administrator()->create();

        $jsonPath = storage_path('app/menkyo-exports/test-karimen-admin-audit.json');
        $mdPath = storage_path('app/menkyo-exports/test-karimen-admin-audit.md');
        File::delete([$jsonPath, $mdPath]);

        $this->mock(KarimenTest1AuditService::class, function ($mock) use ($jsonPath, $mdPath): void {
            $mock->shouldReceive('audit')->once()->andReturn([
                'readiness' => ['fully_publication_ready_questions' => 0],
            ]);
            $mock->shouldReceive('writePrivateReports')->once()->andReturn([
                'json_path' => $jsonPath,
                'markdown_path' => $mdPath,
            ]);
        });

        File::put($jsonPath, '{}');
        File::put($mdPath, 'test');

        Livewire::actingAs($admin)
            ->test(QuestionBankDashboard::class)
            ->call('exportKarimenTest1Audit')
            ->assertSet('statusMessage', fn (?string $message): bool => filled($message)
                && str_contains((string) $message, 'test-karimen-admin-audit.json'));

        $user = $this->learner();
        $examType = $this->examTypeWithRules(1, 0, 1);
        $this->publishedTrueFalse($examType, true);
        $service = app(ExamAttemptService::class);
        $attempt = $service->start($user, $examType, ExamAttemptType::Full);
        $service->recordAnswer($attempt, $attempt->questions->first()->id, 0, true);
        $final = $service->submit($attempt->fresh());

        $this->assertSame(1, $final->score);
        $this->assertTrue($final->passed);
    }
}
