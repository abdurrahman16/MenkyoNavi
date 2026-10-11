<?php

namespace Tests\Feature;

use App\Enums\AccuracyEvidenceStatus;
use App\Enums\AnswerKeyReviewStatus;
use App\Enums\ContentRightsReviewStatus;
use App\Livewire\Admin\OriginalKarimenPilotReviewShow;
use App\Livewire\Admin\QuestionBankIndex;
use App\Livewire\Admin\QuestionBankShow;
use App\Models\QuestionImportBatch;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportEntryTranslation;
use App\Models\User;
use App\Services\Editorial\OriginalKarimenExpansionStagingService;
use App\Services\Editorial\OriginalKarimenPilotStagingService;
use App\Services\Editorial\OriginalKarimenPilotTranslationDraftService;
use App\Services\Exams\QuestionSelectionService;
use Database\Seeders\ExamTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OriginalKarimenExpansion001Test extends TestCase
{
    use RefreshDatabase;

    private function stageExpansion(?string $batch = null, bool $commit = true): void
    {
        $options = $commit ? ['--commit' => true] : ['--dry-run' => true];
        if ($batch !== null) {
            $options['--batch'] = $batch;
        }
        Artisan::call('menkyo:stage-original-karimen-expansion', $options);
    }

    private function stagePilotAndTranslations(): void
    {
        Artisan::call('menkyo:stage-original-karimen-pilot', ['--commit' => true]);
        Artisan::call('menkyo:stage-original-karimen-pilot-translations', ['--commit' => true]);
    }

    #[Test]
    public function expansion_catalog_has_forty_stable_ids_without_pilot_collision(): void
    {
        $service = app(OriginalKarimenExpansionStagingService::class);
        $candidates = $service->candidates();

        $this->assertCount(40, $candidates);
        $ids = [];
        foreach ($candidates as $candidate) {
            $id = (string) $candidate['candidate_id'];
            $this->assertMatchesRegularExpression('/^menkyo_original_karimen_expansion_001:\d+$/', $id);
            $this->assertNotContains($id, $ids);
            $ids[] = $id;

            $this->assertGreaterThanOrEqual(11, (int) $candidate['number']);
            $this->assertLessThanOrEqual(50, (int) $candidate['number']);
            $this->assertContains($candidate['batch'], ['A', 'B', 'C', 'D']);
            $this->assertIsBool($candidate['correct_answer']);
            $this->assertNotSame('', trim((string) $candidate['question_ja']));
            $this->assertNotSame('', trim((string) $candidate['explanation_ja']));
            $this->assertStringContainsString('laws.e-gov.go.jp', (string) $candidate['source_url']);
            $this->assertNotSame('', trim((string) $candidate['exact_provision']));
            $this->assertNotSame('', trim((string) $candidate['research_assessment']));
            $this->assertNotSame('', trim((string) $candidate['evidence_summary']));
        }

        $this->assertSame('menkyo_original_karimen_expansion_001:11', $ids[0]);
        $this->assertSame('menkyo_original_karimen_expansion_001:50', $ids[39]);
        $this->assertNotSame(
            OriginalKarimenPilotStagingService::COLLECTION_KEY,
            OriginalKarimenExpansionStagingService::COLLECTION_KEY,
        );
        $this->assertEmpty(array_intersect(
            $ids,
            array_map(
                fn (int $n): string => OriginalKarimenPilotStagingService::COLLECTION_KEY.':'.$n,
                range(1, 10),
            ),
        ));
    }

    #[Test]
    public function dry_run_is_non_mutating_and_commit_is_idempotent_with_closed_gates(): void
    {
        $this->stageExpansion(commit: false);
        $this->assertSame(
            0,
            QuestionImportEntry::query()
                ->where('source_group', OriginalKarimenExpansionStagingService::SOURCE_GROUP)
                ->count(),
        );

        $this->stageExpansion(commit: true);
        $this->stageExpansion(commit: true);

        $entries = QuestionImportEntry::query()
            ->with('translations')
            ->where('source_group', OriginalKarimenExpansionStagingService::SOURCE_GROUP)
            ->orderBy('original_question_number')
            ->get();

        $this->assertCount(40, $entries);

        foreach ($entries as $entry) {
            $this->assertFalse((bool) $entry->is_published);
            $this->assertNull($entry->promoted_question_id);
            $this->assertFalse((bool) data_get($entry->source_metadata, 'human_accuracy_review'));
            $this->assertFalse((bool) data_get($entry->source_metadata, 'human_editorial_approval'));
            $this->assertFalse((bool) data_get($entry->source_metadata, 'public_exam_eligible'));
            $this->assertNotSame(AnswerKeyReviewStatus::Verified, $entry->answer_key_status);
            $this->assertNotSame(ContentRightsReviewStatus::ApprovedForPublicReuse, $entry->content_rights_status);
            $this->assertSame(AccuracyEvidenceStatus::EvidenceCollected, $entry->accuracy_evidence_status);
            $this->assertNotNull($entry->content_fingerprint);
            $this->assertSame(64, strlen((string) $entry->content_fingerprint));
            $this->assertNotNull($entry->translationFor('ja'));
            $this->assertNotSame('', trim((string) $entry->translationFor('ja')?->question_text));
        }
    }

    #[Test]
    public function batch_filter_stages_only_selected_batch(): void
    {
        $this->stageExpansion(batch: 'A', commit: true);

        $this->assertSame(
            10,
            QuestionImportEntry::query()
                ->where('source_group', OriginalKarimenExpansionStagingService::SOURCE_GROUP)
                ->count(),
        );
        $this->assertSame(
            range(11, 20),
            QuestionImportEntry::query()
                ->where('source_group', OriginalKarimenExpansionStagingService::SOURCE_GROUP)
                ->orderBy('original_question_number')
                ->pluck('original_question_number')
                ->all(),
        );
    }

    #[Test]
    public function human_protected_expansion_rows_are_not_overwritten(): void
    {
        $this->stageExpansion(commit: true);
        $entry = QuestionImportEntry::query()
            ->where('source_group', OriginalKarimenExpansionStagingService::SOURCE_GROUP)
            ->where('original_question_number', 11)
            ->firstOrFail();

        $originalQuestion = $entry->translationFor('ja')?->question_text;
        $entry->forceFill([
            'accuracy_evidence_status' => AccuracyEvidenceStatus::HumanReviewed,
            'accuracy_reviewed_at' => now(),
        ])->save();
        $entry->translationFor('ja')?->forceFill([
            'question_text' => '【人保護テスト】'.$originalQuestion,
        ])->save();

        $this->stageExpansion(commit: true);
        $entry->refresh()->load('translations');

        $this->assertSame('【人保護テスト】'.$originalQuestion, $entry->translationFor('ja')?->question_text);
        $this->assertSame(AccuracyEvidenceStatus::HumanReviewed, $entry->accuracy_evidence_status);
    }

    #[Test]
    public function expansion_staging_preserves_pilot_imported_and_translation_drafts(): void
    {
        config(['services.adsense.enabled' => false]);
        $this->seed(ExamTypeSeeder::class);
        $this->stagePilotAndTranslations();

        $pilotBefore = QuestionImportEntry::query()
            ->where('source_group', OriginalKarimenPilotStagingService::SOURCE_GROUP)
            ->orderBy('original_question_number')
            ->get(['id', 'original_question_number', 'content_fingerprint', 'is_published'])
            ->toArray();

        $draftHashes = QuestionImportEntryTranslation::query()
            ->where('locale', '!=', 'ja')
            ->whereHas('entry', fn ($q) => $q->where('source_group', OriginalKarimenPilotStagingService::SOURCE_GROUP))
            ->where('japanese_reading_data->pilot_translation_meta->drafting_method', OriginalKarimenPilotTranslationDraftService::DRAFTING_METHOD)
            ->orderBy('id')
            ->get(['id', 'locale', 'question_text', 'explanation', 'status'])
            ->map(fn (QuestionImportEntryTranslation $row): string => hash('sha256', json_encode($row->toArray())))
            ->all();
        $this->assertCount(56, $draftHashes);

        $importedFactoryBatch = QuestionImportBatch::factory()->create([
            'source_key' => 'karimen_exam_trainer_en_610',
            'entry_count' => 2,
        ]);
        QuestionImportEntry::factory()->count(2)->create([
            'question_import_batch_id' => $importedFactoryBatch->id,
            'source_group' => 'karimen_test_1',
        ]);
        $importedBefore = QuestionImportEntry::query()->where('source_group', 'karimen_test_1')->count();

        $this->stageExpansion(commit: true);

        $this->assertSame(
            $pilotBefore,
            QuestionImportEntry::query()
                ->where('source_group', OriginalKarimenPilotStagingService::SOURCE_GROUP)
                ->orderBy('original_question_number')
                ->get(['id', 'original_question_number', 'content_fingerprint', 'is_published'])
                ->toArray(),
        );
        $this->assertSame($importedBefore, QuestionImportEntry::query()->where('source_group', 'karimen_test_1')->count());

        $draftHashesAfter = QuestionImportEntryTranslation::query()
            ->where('locale', '!=', 'ja')
            ->whereHas('entry', fn ($q) => $q->where('source_group', OriginalKarimenPilotStagingService::SOURCE_GROUP))
            ->where('japanese_reading_data->pilot_translation_meta->drafting_method', OriginalKarimenPilotTranslationDraftService::DRAFTING_METHOD)
            ->orderBy('id')
            ->get(['id', 'locale', 'question_text', 'explanation', 'status'])
            ->map(fn (QuestionImportEntryTranslation $row): string => hash('sha256', json_encode($row->toArray())))
            ->all();
        $this->assertSame($draftHashes, $draftHashesAfter);

        $offer = app(QuestionSelectionService::class)->practiceOffer('karimen', null, 'ja');
        $this->assertFalse($offer['full']['can_start']);

        $this->assertSame(
            0,
            QuestionImportEntry::query()
                ->where('source_group', OriginalKarimenExpansionStagingService::SOURCE_GROUP)
                ->where('is_published', true)
                ->count(),
        );

        $this->assertFalse((bool) config('services.adsense.enabled'));
        $this->get(route('home'))->assertSuccessful()->assertDontSee('data-adsense-auto', false);
    }

    #[Test]
    public function admin_can_inspect_expansion_and_guests_are_denied(): void
    {
        $this->stageExpansion(commit: true);
        $entry = QuestionImportEntry::query()
            ->where('source_group', OriginalKarimenExpansionStagingService::SOURCE_GROUP)
            ->where('original_question_number', 11)
            ->firstOrFail();

        $this->get(route('admin.questions.index', ['sourceKey' => OriginalKarimenExpansionStagingService::COLLECTION_KEY]))
            ->assertRedirect(route('login'));

        $learner = User::factory()->create(['is_admin' => false]);
        $this->actingAs($learner)
            ->get(route('admin.questions.show', $entry))
            ->assertForbidden();

        $admin = User::factory()->administrator()->create();
        Livewire::actingAs($admin)
            ->test(QuestionBankIndex::class, ['sourceKey' => OriginalKarimenExpansionStagingService::COLLECTION_KEY])
            ->assertSee('menkyo_original_karimen_expansion_001', false)
            ->assertSee('#11', false);

        Livewire::actingAs($admin)
            ->test(QuestionBankShow::class, ['entry' => $entry->fresh(['translations', 'batch'])])
            ->assertSee(__('admin.original_expansion_evidence_panel_title'), false)
            ->assertSee('supported_as_written', false)
            ->assertSee('警察官等が交通整理のため', false)
            ->assertSeeHtml('data-expansion-batch');
    }

    #[Test]
    public function pilot_accuracy_workbench_and_demo_exam_paths_remain_functional(): void
    {
        $this->seed(ExamTypeSeeder::class);
        $this->stagePilotAndTranslations();
        $this->stageExpansion(commit: true);

        $admin = User::factory()->administrator()->create();
        $pilot = QuestionImportEntry::query()
            ->where('source_group', OriginalKarimenPilotStagingService::SOURCE_GROUP)
            ->where('original_question_number', 2)
            ->firstOrFail();

        Livewire::actingAs($admin)
            ->test(OriginalKarimenPilotReviewShow::class, ['entry' => $pilot])
            ->assertSuccessful()
            ->assertSee('menkyo_original_karimen_pilot_001:2', false);

        $this->get(route('exams.karimen'))
            ->assertSuccessful();

        $offer = app(QuestionSelectionService::class)->practiceOffer('karimen', null, 'ja');
        $this->assertFalse($offer['full']['can_start']);
    }

    #[Test]
    public function no_human_approvals_are_fabricated_for_expansion_candidates(): void
    {
        $this->stageExpansion(commit: true);

        $this->assertSame(
            0,
            QuestionImportEntry::query()
                ->where('source_group', OriginalKarimenExpansionStagingService::SOURCE_GROUP)
                ->where('accuracy_evidence_status', AccuracyEvidenceStatus::HumanReviewed)
                ->count(),
        );
        $this->assertSame(
            0,
            QuestionImportEntry::query()
                ->where('source_group', OriginalKarimenExpansionStagingService::SOURCE_GROUP)
                ->whereNotNull('accuracy_reviewed_by')
                ->count(),
        );
        $this->assertSame(
            0,
            QuestionImportEntry::query()
                ->where('source_group', OriginalKarimenExpansionStagingService::SOURCE_GROUP)
                ->where('answer_key_status', AnswerKeyReviewStatus::Verified)
                ->count(),
        );
    }
}
