<?php

namespace Tests\Feature;

use App\Enums\AccuracyEvidenceStatus;
use App\Enums\AnswerKeyReviewStatus;
use App\Enums\BatchContentRightsStatus;
use App\Enums\ContentRightsReviewStatus;
use App\Enums\ExamAttemptType;
use App\Enums\FigureReviewStatus;
use App\Enums\QuestionImportMediaStatus;
use App\Livewire\Admin\QuestionBankDashboard;
use App\Livewire\Admin\QuestionBankShow;
use App\Models\QuestionImportBatch;
use App\Models\QuestionImportEntry;
use App\Models\User;
use App\Services\Editorial\EditorialReviewService;
use App\Services\Editorial\KarimenTest1PriorityReviewService;
use App\Services\Editorial\KarimenTest1SignAssetResolver;
use App\Services\Exams\ExamAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Exams\CreatesPracticeExams;
use Tests\TestCase;

class KarimenTest1PriorityReviewTest extends TestCase
{
    use CreatesPracticeExams;
    use RefreshDatabase;

    /**
     * @return array{0: QuestionImportBatch, 1: list<QuestionImportEntry>}
     */
    private function seedKarimenTest1WithFigures(): array
    {
        fake()->unique(true);

        $html = <<<'HTML'
const SIGNS={
  pedbike: `<svg xmlns="http://www.w3.org/2000/svg"><circle r="10"/></svg>`,
  closedVeh: `<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>`,
  diagram: `<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0"/></svg>`,
  notwostep: `<svg xmlns="http://www.w3.org/2000/svg"><text>x</text></svg>`,
  mark: `<svg xmlns="http://www.w3.org/2000/svg"><line x1="0" y1="0" x2="1" y2="1"/></svg>`,
  slow: `<svg xmlns="http://www.w3.org/2000/svg"><text>徐行</text></svg>`,
  slippery: `<svg xmlns="http://www.w3.org/2000/svg"><polygon points="0,0 1,1 0,1"/></svg>`,
  strTruck: `<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>`,
  handsig: `<svg xmlns="http://www.w3.org/2000/svg"><circle/></svg>`,
  noUturn: `<svg xmlns="http://www.w3.org/2000/svg"><path/></svg>`,
  zone: `<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>`,
  police: `<svg xmlns="http://www.w3.org/2000/svg"><circle/></svg>`,
  noOver: `<svg xmlns="http://www.w3.org/2000/svg"><line/></svg>`,
};
HTML;

        $relative = 'menkyo-imports/test-karimen-priority.html';
        $absolute = storage_path('app/'.$relative);
        File::ensureDirectoryExists(dirname($absolute));
        File::put($absolute, $html);

        $batch = QuestionImportBatch::factory()->create([
            'source_key' => 'karimen_exam_trainer_en_610',
            'source_filename' => 'test-karimen-priority.html',
            'source_relative_path' => $relative,
            'content_rights_status' => BatchContentRightsStatus::NoEvidenceSubmitted,
        ]);

        $figureMap = [
            2 => 'pedbike',
            6 => 'closedVeh',
            8 => 'diagram',
            10 => 'diagram',
            13 => 'notwostep',
            22 => 'mark',
            28 => 'slow',
            31 => 'slippery',
            33 => 'strTruck',
            35 => 'handsig',
            39 => 'noUturn',
            42 => 'zone',
            45 => 'police',
            47 => 'noOver',
        ];

        $entries = [];
        for ($number = 1; $number <= 50; $number++) {
            $isFigure = array_key_exists($number, $figureMap);
            $entries[] = QuestionImportEntry::factory()->create([
                'question_import_batch_id' => $batch->id,
                'source_group' => 'karimen_test_1',
                'original_question_number' => $number,
                'english_question' => "Priority research question {$number} must stay immutable.",
                'english_explanation' => "Explanation {$number}",
                'correct_answer' => in_array($number, [4, 19], true) ? true : ($number % 2 === 0),
                'sign_key' => $isFigure ? $figureMap[$number] : null,
                'figure_caption' => $isFigure ? "Caption {$number}" : null,
                'media_availability' => $isFigure ? QuestionImportMediaStatus::Available : QuestionImportMediaStatus::None,
                'review_flags' => in_array($number, [4, 23], true) ? ['ambiguous_terminology'] : [],
                'accuracy_evidence_status' => match ($number) {
                    4 => AccuracyEvidenceStatus::NeedsClarification,
                    38 => AccuracyEvidenceStatus::ConflictingEvidence,
                    default => AccuracyEvidenceStatus::EvidenceCollected,
                },
                'figure_review_status' => $isFigure
                    ? FigureReviewStatus::FigurePresentUnverified
                    : FigureReviewStatus::NotApplicable,
                'answer_key_status' => AnswerKeyReviewStatus::VerificationPending,
                'content_rights_status' => ContentRightsReviewStatus::Unclear,
                'is_published' => false,
                'source_entry_checksum' => hash('sha256', 'priority|'.$number),
            ]);
        }

        // Force Q19 false like production.
        $q19 = collect($entries)->firstWhere('original_question_number', 19);
        $q19->forceFill([
            'english_question' => 'When a vehicle is driven on a learner\'s permit for practice, a "learner driver" sign must be displayed on either the front OR rear of the vehicle.',
            'correct_answer' => false,
            'english_explanation' => 'The sign must be displayed on BOTH the front and rear of the vehicle.',
        ])->save();

        $q38 = collect($entries)->firstWhere('original_question_number', 38);
        $q38->forceFill([
            'english_question' => 'At an uncontrolled intersection with poor visibility, vehicles WITH right of way must slow down, even when proceeding.',
            'correct_answer' => false,
            'english_explanation' => 'It is not always necessary to slow down when proceeding on a road with right of way.',
            'accuracy_evidence_status' => AccuracyEvidenceStatus::ConflictingEvidence,
        ])->save();

        $q4 = collect($entries)->firstWhere('original_question_number', 4);
        $q4->forceFill([
            'english_question' => 'It is important for drivers not to be selfish, but to have a give-and-take spirit, consider others, and respect pedestrians and/or other drivers.',
            'correct_answer' => true,
            'english_explanation' => 'This is an attitude required for a driver.',
        ])->save();

        $q23 = collect($entries)->firstWhere('original_question_number', 23);
        $q23->forceFill([
            'english_question' => 'It is advisable for automobiles to proceed at the maximum speed limit in order to make traffic flow smoothly.',
            'correct_answer' => false,
            'english_explanation' => 'Travel at a SAFE speed appropriate for road, traffic and weather conditions, within the limit.',
            'review_flags' => ['soft_or_awkward_phrasing'],
        ])->save();

        return [$batch->fresh(), QuestionImportEntry::query()->where('source_group', 'karimen_test_1')->orderBy('original_question_number')->get()->all()];
    }

    #[Test]
    public function test_priority_packets_link_correct_entries_and_preserve_conflicting_evidence(): void
    {
        [$batch, $entries] = $this->seedKarimenTest1WithFigures();
        $before = collect($entries)->mapWithKeys(fn (QuestionImportEntry $e) => [
            $e->id => [
                $e->english_question,
                $e->correct_answer,
                $e->source_entry_checksum,
                $e->is_published,
                $e->answer_key_status->value,
                $e->accuracy_evidence_status->value,
            ],
        ])->all();

        $report = app(KarimenTest1PriorityReviewService::class)->build();

        $this->assertCount(4, $report['section_a_priority_questions']);
        $byNumber = collect($report['section_a_priority_questions'])->keyBy('original_question_number');

        $this->assertSame(
            collect($entries)->firstWhere('original_question_number', 4)->id,
            $byNumber[4]['source_entry_id']
        );
        $this->assertSame(
            collect($entries)->firstWhere('original_question_number', 23)->id,
            $byNumber[23]['source_entry_id']
        );
        $this->assertTrue($byNumber[4]['proposed_correction']['recommend_withhold']);
        $this->assertSame('conflicting_evidence', $byNumber[38]['historical_flags']['accuracy_evidence_status']);
        $this->assertNotEmpty($byNumber[38]['contradictory_evidence']);
        $this->assertNotEmpty($byNumber[38]['article_comparison']);
        $this->assertStringContainsString('仮免許', $byNumber[19]['proposed_correction']['proposed_clarified_statement_en']);
        $this->assertStringContainsString('初心運転者', $byNumber[19]['unresolved_ambiguity']);

        $after = QuestionImportEntry::query()
            ->where('source_group', 'karimen_test_1')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (QuestionImportEntry $e) => [
                $e->id => [
                    $e->english_question,
                    $e->correct_answer,
                    $e->source_entry_checksum,
                    $e->is_published,
                    $e->answer_key_status->value,
                    $e->accuracy_evidence_status->value,
                ],
            ])->all();

        $this->assertSame($before, $after);
        $this->assertSame(0, $report['section_e_remaining_blockers']['publication_ready_count']);
        $this->assertTrue($report['disclaimer']['does_not_set_human_reviewed']);
        $this->assertSame(0, $report['integrity_check']['human_reviewed_accuracy_count']);
        $this->assertSame($batch->id, $report['section_c_source_rights_review']['question_import_batch_id']);
        $this->assertSame(BatchContentRightsStatus::NoEvidenceSubmitted->value, $report['section_c_source_rights_review']['content_rights_status']);
    }

    #[Test]
    public function test_figure_inventory_covers_fourteen_and_separates_presence_from_verification(): void
    {
        $this->seedKarimenTest1WithFigures();
        $report = app(KarimenTest1PriorityReviewService::class)->build();

        $this->assertCount(14, $report['section_b_figure_inventory']);
        $numbers = collect($report['section_b_figure_inventory'])->pluck('original_question_number')->sort()->values()->all();
        $this->assertSame([2, 6, 8, 10, 13, 22, 28, 31, 33, 35, 39, 42, 45, 47], $numbers);

        foreach ($report['section_b_figure_inventory'] as $row) {
            $this->assertTrue($row['asset_check']['present_in_signs_object']);
            $this->assertTrue($row['asset_check']['svg_extractable']);
            $this->assertFalse($row['does_file_existence_equal_human_verification']);
            $this->assertFalse($row['editorial_checks_pending']['figure_reuse_rights_documented']);
            $this->assertNotSame(FigureReviewStatus::ReviewedSufficient->value, $row['figure_review_status_db']);
        }
    }

    #[Test]
    public function test_proposed_corrections_do_not_mutate_source_and_rights_unknown_stay_blocked(): void
    {
        $this->seedKarimenTest1WithFigures();
        $service = app(KarimenTest1PriorityReviewService::class);
        $report = $service->build();

        foreach ($report['section_d_proposed_corrections'] as $row) {
            $this->assertFalse($row['applied_to_imported_source']);
            $entry = QuestionImportEntry::query()->findOrFail($row['source_entry_id']);
            $this->assertTrue($row['proposal']['keep_imported_answer_unchanged']);
            $this->assertSame($row['proposal']['imported_answer'], (bool) $entry->correct_answer);
            $this->assertContains('content_rights_unclear', $entry->computePublicationBlockers());
        }

        $paths = $service->writePrivateReports(
            $report,
            storage_path('app/menkyo-exports/test-priority-review.json'),
            storage_path('app/menkyo-exports/test-priority-review.md'),
        );
        $this->assertFileExists($paths['json_path']);
        $this->assertStringNotContainsString('/public/', str_replace('\\', '/', $paths['json_path']));
        $leak = $this->get('/storage/menkyo-exports/test-priority-review.json');
        $this->assertContains($leak->status(), [403, 404]);
    }

    #[Test]
    public function test_admin_priority_export_and_figure_preview_require_authorization(): void
    {
        [$batch, $entries] = $this->seedKarimenTest1WithFigures();
        $entry = collect($entries)->firstWhere('original_question_number', 2);
        $guest = User::factory()->create(['is_admin' => false]);
        $admin = User::factory()->administrator()->create();

        $this->actingAs($guest)->get(route('admin.questions.dashboard'))->assertForbidden();
        $this->actingAs($guest)->get(route('admin.questions.show', $entry))->assertForbidden();

        Livewire::actingAs($admin)
            ->test(QuestionBankDashboard::class)
            ->call('exportKarimenTest1PriorityReview')
            ->assertSet('statusMessage', fn (?string $message): bool => filled($message));

        Livewire::actingAs($admin)
            ->test(QuestionBankShow::class, ['entry' => $entry->fresh(['batch'])])
            ->assertSee('data-evidence-review-panel', false)
            ->assertSee('pedbike', false);

        $resolver = app(KarimenTest1SignAssetResolver::class);
        $resolved = $resolver->resolve($entry->fresh(['batch']));
        $this->assertTrue($resolved['present_in_signs_object']);
        $this->assertNotNull($resolved['svg_markup']);
        $this->assertStringContainsString('<svg', $resolver->sanitizeSvgMarkup($resolved['svg_markup']));
    }

    #[Test]
    public function test_batch_content_rights_update_requires_evidence_for_cleared_status(): void
    {
        Storage::fake('local');

        $admin = User::factory()->administrator()->create();
        $batch = QuestionImportBatch::factory()->create();
        $first = QuestionImportEntry::factory()->create([
            'question_import_batch_id' => $batch->id,
            'source_group' => 'karimen_test_1',
            'original_question_number' => 1,
            'content_rights_status' => ContentRightsReviewStatus::Unclear,
        ]);

        $editorial = app(EditorialReviewService::class);

        try {
            $editorial->updateBatchContentRights(
                $batch,
                $admin,
                BatchContentRightsStatus::ClearedByAuthorizedHuman,
                ['permission_notes' => null, 'rights_evidence' => null],
            );
            $this->fail('Expected validation exception for cleared status without evidence.');
        } catch (ValidationException $exception) {
            $this->assertNotEmpty($exception->errors());
        }

        $first->refresh();
        $this->assertSame(ContentRightsReviewStatus::Unclear, $first->content_rights_status);
        $this->assertSame(BatchContentRightsStatus::NoEvidenceSubmitted, $batch->fresh()->content_rights_status);
        $this->assertSame(0, QuestionImportEntry::query()->where('is_published', true)->count());
        $this->assertSame(0, QuestionImportEntry::query()->where('accuracy_evidence_status', AccuracyEvidenceStatus::HumanReviewed)->count());
    }

    #[Test]
    public function test_admin_can_save_batch_content_rights_with_private_document_and_exam_still_works(): void
    {
        Storage::fake('local');

        $admin = User::factory()->administrator()->create();
        $batch = QuestionImportBatch::factory()->create();
        $entry = QuestionImportEntry::factory()->create([
            'question_import_batch_id' => $batch->id,
            'source_group' => 'karimen_test_1',
            'original_question_number' => 19,
            'content_rights_status' => ContentRightsReviewStatus::Unclear,
        ]);

        $file = UploadedFile::fake()->create('permission.txt', 10, 'text/plain');

        Livewire::actingAs($admin)
            ->test(QuestionBankShow::class, ['entry' => $entry])
            ->set('batchContentRightsStatus', BatchContentRightsStatus::PermissionDocumentSupplied->value)
            ->set('batchPermissionNotes', 'Written permission on file for staging review.')
            ->set('batchRightsDocument', $file)
            ->call('saveBatchContentRights')
            ->assertHasNoErrors();

        $batch->refresh();
        $this->assertSame(BatchContentRightsStatus::PermissionDocumentSupplied, $batch->content_rights_status);
        $this->assertNotNull($batch->rights_document_path);
        $this->assertStringStartsWith('private/menkyo-rights/', str_replace('\\', '/', (string) $batch->rights_document_path));
        $this->assertSame(ContentRightsReviewStatus::Unclear, $entry->fresh()->content_rights_status);
        $this->assertFalse($entry->fresh()->is_published);
        $this->assertSame(AnswerKeyReviewStatus::VerificationPending, $entry->fresh()->answer_key_status);

        $leak = $this->get('/storage/'.ltrim((string) $batch->rights_document_path, '/'));
        $this->assertContains($leak->status(), [403, 404]);

        $user = $this->learner();
        $examType = $this->examTypeWithRules(1, 0, 1);
        $this->publishedTrueFalse($examType, true);
        $service = app(ExamAttemptService::class);
        $attempt = $service->start($user, $examType, ExamAttemptType::Full);
        $service->recordAnswer($attempt, $attempt->questions->first()->id, 0, true);
        $final = $service->submit($attempt->fresh());
        $this->assertSame(1, $final->score);
    }
}
