<?php

namespace Tests\Feature;

use App\Enums\HumanAccuracyReviewDecision;
use App\Enums\StagedTranslationStatus;
use App\Livewire\Admin\OriginalKarimenPilotReviewIndex;
use App\Livewire\Admin\QuestionBankShow;
use App\Models\QuestionImportBatch;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportEntryTranslation;
use App\Models\User;
use App\Services\Editorial\OriginalKarimenPilotAccuracyReviewService;
use App\Services\Editorial\OriginalKarimenPilotStagingService;
use App\Services\Editorial\OriginalKarimenPilotTranslationDraftService;
use App\Services\Exams\QuestionSelectionService;
use App\Support\SupportedLocale;
use Database\Seeders\ExamTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OriginalKarimenPilotTranslationDraftsTest extends TestCase
{
    use RefreshDatabase;

    private function stageSources(): void
    {
        Artisan::call('menkyo:stage-original-karimen-pilot', ['--commit' => true]);
    }

    private function stageTranslations(): array
    {
        return app(OriginalKarimenPilotTranslationDraftService::class)->stage(dryRun: false);
    }

    private function pilotEntry(int $number): QuestionImportEntry
    {
        return QuestionImportEntry::query()
            ->with(['translations', 'batch'])
            ->where('source_group', OriginalKarimenPilotStagingService::SOURCE_GROUP)
            ->where('original_question_number', $number)
            ->firstOrFail();
    }

    #[Test]
    public function eligible_candidates_come_from_audit_state_and_exclude_clarification_items(): void
    {
        $this->stageSources();
        $drafts = app(OriginalKarimenPilotTranslationDraftService::class);

        $this->assertSame([2, 3, 4, 5, 6, 7, 9, 10], $drafts->eligibleCandidateNumbers());
        $this->assertCount(2, $drafts->withheldCandidates());
        $this->assertSame(
            ['menkyo_original_karimen_pilot_001:1', 'menkyo_original_karimen_pilot_001:8'],
            collect($drafts->withheldCandidates())->pluck('candidate_id')->all(),
        );
    }

    #[Test]
    public function candidates_one_and_eight_remain_withheld_while_issues_unresolved(): void
    {
        $this->stageSources();
        $summary = $this->stageTranslations();

        $this->assertSame(8, $summary['eligible_count']);
        $this->assertSame(2, $summary['withheld_count']);

        foreach ([1, 8] as $number) {
            $entry = $this->pilotEntry($number);
            foreach (app(OriginalKarimenPilotTranslationDraftService::class)->targetLocales() as $locale) {
                $translation = $entry->translationFor($locale);
                // Staging may keep a non-canonical English gloss; Task 46 must not add draft locales.
                if ($locale === 'en') {
                    $this->assertNull(data_get($translation?->japanese_reading_data, 'pilot_translation_meta.created_by_task'));
                } else {
                    $this->assertNull($translation);
                }
            }
        }
    }

    #[Test]
    public function ten_source_ids_remain_unchanged_and_locales_are_valid(): void
    {
        $this->stageSources();
        $ids = QuestionImportEntry::query()
            ->where('source_group', OriginalKarimenPilotStagingService::SOURCE_GROUP)
            ->orderBy('original_question_number')
            ->get()
            ->map(fn (QuestionImportEntry $entry): string => (string) data_get($entry->source_metadata, 'candidate_id'))
            ->all();

        $this->assertSame([
            'menkyo_original_karimen_pilot_001:1',
            'menkyo_original_karimen_pilot_001:2',
            'menkyo_original_karimen_pilot_001:3',
            'menkyo_original_karimen_pilot_001:4',
            'menkyo_original_karimen_pilot_001:5',
            'menkyo_original_karimen_pilot_001:6',
            'menkyo_original_karimen_pilot_001:7',
            'menkyo_original_karimen_pilot_001:8',
            'menkyo_original_karimen_pilot_001:9',
            'menkyo_original_karimen_pilot_001:10',
        ], $ids);

        foreach (app(OriginalKarimenPilotTranslationDraftService::class)->targetLocales() as $locale) {
            $this->assertTrue(SupportedLocale::isSupported($locale));
        }
    }

    #[Test]
    public function staged_drafts_have_text_fingerprints_answers_and_draft_status_only(): void
    {
        $this->stageSources();
        $summary = $this->stageTranslations();
        $drafts = app(OriginalKarimenPilotTranslationDraftService::class);

        // English gloss rows may already exist from Japanese staging; expect creates+updates = 56.
        $this->assertSame(56, $summary['created_count'] + $summary['updated_count']);
        $this->assertSame(56, array_sum($summary['per_locale']));

        foreach ($drafts->eligibleCandidateNumbers() as $number) {
            $entry = $this->pilotEntry($number);
            $answer = $entry->correct_answer;
            $fingerprint = $drafts->sourceContentFingerprint($entry);

            foreach ($drafts->targetLocales() as $locale) {
                $translation = $entry->translationFor($locale);
                $this->assertNotNull($translation);
                $this->assertNotSame('', trim((string) $translation->question_text));
                $this->assertNotSame('', trim((string) $translation->explanation));
                $this->assertSame($fingerprint, data_get($translation->japanese_reading_data, 'pilot_translation_meta.source_content_fingerprint'));
                $this->assertFalse((bool) data_get($translation->japanese_reading_data, 'pilot_translation_meta.native_reviewed'));
                $this->assertNull($translation->reviewed_at);
                $this->assertFalse($translation->status->isReviewedOrHigher());
                $this->assertContains($translation->status, [
                    StagedTranslationStatus::Draft,
                    StagedTranslationStatus::NeedsReview,
                ]);
            }

            $entry->refresh();
            $this->assertSame($answer, $entry->correct_answer);
            $this->assertFalse((bool) $entry->is_published);
            $this->assertFalse((bool) data_get($entry->source_metadata, 'human_accuracy_review'));
            $this->assertFalse((bool) data_get($entry->source_metadata, 'translation_reviewed'));
        }
    }

    #[Test]
    public function staging_is_idempotent_and_prevents_duplicate_locale_rows(): void
    {
        $this->stageSources();
        $first = $this->stageTranslations();
        $second = $this->stageTranslations();

        $this->assertSame(56, $first['created_count'] + $first['updated_count']);
        $this->assertSame(0, $second['created_count']);
        $this->assertSame(56, $second['updated_count']);

        $this->assertSame(
            56,
            QuestionImportEntryTranslation::query()
                ->whereHas('entry', fn ($q) => $q->where('source_group', OriginalKarimenPilotStagingService::SOURCE_GROUP))
                ->whereIn('locale', app(OriginalKarimenPilotTranslationDraftService::class)->targetLocales())
                ->get()
                ->filter(fn (QuestionImportEntryTranslation $row): bool => data_get($row->japanese_reading_data, 'pilot_translation_meta.created_by_task') === 46)
                ->count(),
        );
    }

    #[Test]
    public function human_edited_reviewed_translations_are_protected(): void
    {
        $this->stageSources();
        $this->stageTranslations();

        $entry = $this->pilotEntry(2);
        $translation = $entry->translationFor('en');
        $this->assertNotNull($translation);
        $protectedText = 'HUMAN EDITED ENGLISH STEM MUST REMAIN';
        $translation->forceFill([
            'question_text' => $protectedText,
            'status' => StagedTranslationStatus::Reviewed,
            'reviewed_at' => now(),
            'translator_note' => 'human-edited native draft',
        ])->save();

        $summary = $this->stageTranslations();
        $this->assertGreaterThanOrEqual(1, $summary['protected_count']);
        $this->assertSame($protectedText, $entry->fresh(['translations'])->translationFor('en')?->question_text);
    }

    #[Test]
    public function changed_source_version_marks_translations_stale_and_blocks_publication_eligibility(): void
    {
        $this->stageSources();
        $this->stageTranslations();
        $drafts = app(OriginalKarimenPilotTranslationDraftService::class);
        $entry = $this->pilotEntry(2);
        $translation = $entry->translationFor('en');
        $this->assertNotNull($translation);
        $this->assertFalse($drafts->translationIsStale($entry, $translation));

        $ja = $entry->translationFor('ja');
        $this->assertNotNull($ja);
        $ja->forceFill(['question_text' => $ja->question_text.'【実質変更】'])->save();

        $entry = $entry->fresh(['translations', 'batch']);
        $drafts->refreshStaleTranslationFlags($entry);
        $translation = $entry->fresh(['translations'])->translationFor('en');
        $this->assertNotNull($translation);
        $this->assertTrue($drafts->translationIsStale($entry, $translation));
        $this->assertContains('source_version_mismatch', $translation->quality_flags ?? []);
        $this->assertFalse($drafts->translationEligibleForPublication($entry, $translation));
        $this->assertNotSame(StagedTranslationStatus::ReadyForPublication, $translation->status);
    }

    #[Test]
    public function rejected_or_deferred_accuracy_decisions_block_eligibility(): void
    {
        $this->stageSources();
        $admin = User::factory()->administrator()->create();
        $entry = $this->pilotEntry(2);
        $reviews = app(OriginalKarimenPilotAccuracyReviewService::class);

        $reviews->submitDecision($entry, $admin, [
            'decision' => HumanAccuracyReviewDecision::Deferred,
            'notes' => 'Deferring candidate two in translation eligibility fixture only.',
            'expected_content_fingerprint' => $reviews->reviewContentFingerprint($entry),
        ]);

        $eligible = app(OriginalKarimenPilotTranslationDraftService::class)->eligibleCandidateNumbers();
        $this->assertNotContains(2, $eligible);
        $this->assertContains(3, $eligible);
    }

    #[Test]
    public function imported_bank_records_and_exam_pools_remain_untouched(): void
    {
        config(['services.adsense.enabled' => false]);
        $this->seed(ExamTypeSeeder::class);

        $batch = QuestionImportBatch::factory()->create([
            'source_key' => 'karimen_exam_trainer_en_610',
            'entry_count' => 1024,
        ]);
        QuestionImportEntry::factory()->count(3)->create([
            'question_import_batch_id' => $batch->id,
            'source_group' => 'karimen_test_1',
            'english_question' => 'IMPORTED UNCHANGED',
        ]);
        $before = QuestionImportEntry::query()->where('source_group', 'karimen_test_1')->pluck('english_question')->all();

        $this->stageSources();
        $this->stageTranslations();

        $this->assertSame(
            $before,
            QuestionImportEntry::query()->where('source_group', 'karimen_test_1')->orderBy('id')->pluck('english_question')->all(),
        );
        $this->assertSame(1024, (int) $batch->fresh()->entry_count);

        $offer = app(QuestionSelectionService::class)->practiceOffer('karimen', null, 'ja');
        $this->assertFalse($offer['full']['can_start']);
        $this->assertArrayHasKey('demo', $offer);

        $this->assertFalse((bool) config('services.adsense.enabled'));
        $this->get(route('home'))->assertSuccessful()->assertDontSee('data-adsense-auto', false);
    }

    #[Test]
    public function admin_can_see_withheld_reasons_and_guests_are_denied(): void
    {
        $this->stageSources();
        $this->stageTranslations();
        $admin = User::factory()->administrator()->create();
        $guest = User::factory()->create(['is_admin' => false]);
        $entryOne = $this->pilotEntry(1);
        $entryTwo = $this->pilotEntry(2);

        $this->get(route('admin.questions.show', $entryTwo))->assertRedirect(route('login'));

        $this->actingAs($guest)
            ->get(route('admin.questions.show', $entryTwo))
            ->assertForbidden();

        Livewire::actingAs($admin)
            ->test(OriginalKarimenPilotReviewIndex::class)
            ->assertSee('menkyo_original_karimen_pilot_001:1', false)
            ->assertSee('supported_with_clarification', false)
            ->assertSee('pedestrian', false);

        Livewire::actingAs($admin)
            ->test(QuestionBankShow::class, ['entry' => $entryOne->fresh(['translations', 'batch'])])
            ->assertSee(__('admin.pilot_translation_withheld', ['id' => 'menkyo_original_karimen_pilot_001:1']), false);

        Livewire::actingAs($admin)
            ->test(QuestionBankShow::class, ['entry' => $entryTwo->fresh(['translations', 'batch'])])
            ->assertSee('ai_assisted_controlled_draft_task_46', false)
            ->assertSee(__('admin.pilot_translation_native').':', false)
            ->assertSee('false', false);
    }
}
