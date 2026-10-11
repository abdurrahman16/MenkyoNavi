<?php

namespace Tests\Feature;

use App\Enums\AccuracyEvidenceStatus;
use App\Enums\SourceRevisionDecision;
use App\Livewire\Admin\OriginalKarimenPilotReviewIndex;
use App\Livewire\Admin\OriginalKarimenPilotReviewShow;
use App\Models\QuestionImportBatch;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportEntryTranslation;
use App\Models\QuestionImportReviewAudit;
use App\Models\User;
use App\Services\Editorial\OriginalKarimenExpansionStagingService;
use App\Services\Editorial\OriginalKarimenPilotAccuracyReviewService;
use App\Services\Editorial\OriginalKarimenPilotSourceRevisionService;
use App\Services\Editorial\OriginalKarimenPilotStagingService;
use App\Services\Editorial\OriginalKarimenPilotTranslationDraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OriginalKarimen50ReviewSprintTest extends TestCase
{
    use RefreshDatabase;

    private function stageAllOriginal(): void
    {
        Artisan::call('menkyo:stage-original-karimen-pilot', ['--commit' => true]);
        Artisan::call('menkyo:stage-original-karimen-pilot-translations', ['--commit' => true]);
        Artisan::call('menkyo:stage-original-karimen-expansion', ['--commit' => true]);
    }

    #[Test]
    public function fifty_unique_original_candidate_ids_are_staged_across_both_collections(): void
    {
        $this->stageAllOriginal();

        $pilot = QuestionImportEntry::query()
            ->where('source_group', OriginalKarimenPilotStagingService::SOURCE_GROUP)
            ->pluck('original_question_number')
            ->sort()
            ->values()
            ->all();
        $expansion = QuestionImportEntry::query()
            ->where('source_group', OriginalKarimenExpansionStagingService::SOURCE_GROUP)
            ->pluck('original_question_number')
            ->sort()
            ->values()
            ->all();

        $this->assertSame(range(1, 10), $pilot);
        $this->assertSame(range(11, 50), $expansion);
        $this->assertSame(
            50,
            app(OriginalKarimenPilotAccuracyReviewService::class)->originalEntriesQuery()->count(),
        );
    }

    #[Test]
    public function expansion_dry_run_is_non_mutating_and_commit_is_idempotent(): void
    {
        Artisan::call('menkyo:stage-original-karimen-pilot', ['--commit' => true]);
        $before = QuestionImportEntry::query()->count();

        Artisan::call('menkyo:stage-original-karimen-expansion', ['--dry-run' => true]);
        $this->assertSame($before, QuestionImportEntry::query()->count());
        $this->assertSame(
            0,
            QuestionImportEntry::query()
                ->where('source_group', OriginalKarimenExpansionStagingService::SOURCE_GROUP)
                ->count(),
        );

        Artisan::call('menkyo:stage-original-karimen-expansion', ['--commit' => true]);
        $firstIds = QuestionImportEntry::query()
            ->where('source_group', OriginalKarimenExpansionStagingService::SOURCE_GROUP)
            ->orderBy('id')
            ->pluck('id')
            ->all();
        $this->assertCount(40, $firstIds);

        Artisan::call('menkyo:stage-original-karimen-expansion', ['--commit' => true]);
        $secondIds = QuestionImportEntry::query()
            ->where('source_group', OriginalKarimenExpansionStagingService::SOURCE_GROUP)
            ->orderBy('id')
            ->pluck('id')
            ->all();
        $this->assertSame($firstIds, $secondIds);
        $this->assertSame(40, count($secondIds));
    }

    #[Test]
    public function human_protected_expansion_records_are_not_overwritten(): void
    {
        $this->stageAllOriginal();

        $entry = QuestionImportEntry::query()
            ->with('translations')
            ->where('source_group', OriginalKarimenExpansionStagingService::SOURCE_GROUP)
            ->where('original_question_number', 11)
            ->firstOrFail();
        $originalQuestion = (string) $entry->translationFor('ja')?->question_text;
        $entry->forceFill([
            'accuracy_evidence_status' => AccuracyEvidenceStatus::HumanReviewed,
            'accuracy_reviewed_at' => now(),
        ])->save();
        $entry->translationFor('ja')?->forceFill([
            'question_text' => '【人保護テスト】'.$originalQuestion,
        ])->save();

        Artisan::call('menkyo:stage-original-karimen-expansion', ['--commit' => true]);

        $fresh = $entry->fresh(['translations']);
        $this->assertSame('【人保護テスト】'.$originalQuestion, $fresh->translationFor('ja')?->question_text);
        $this->assertSame(AccuracyEvidenceStatus::HumanReviewed, $fresh->accuracy_evidence_status);
    }

    #[Test]
    public function both_collections_appear_in_admin_accuracy_review_queue(): void
    {
        $this->stageAllOriginal();
        $admin = User::factory()->administrator()->create();

        Livewire::actingAs($admin)
            ->test(OriginalKarimenPilotReviewIndex::class)
            ->set('filter', 'all')
            ->set('collection', 'all')
            ->assertSee('menkyo_original_karimen_pilot_001:1', false)
            ->assertSee('menkyo_original_karimen_expansion_001:11', false)
            ->assertSee('menkyo_original_karimen_expansion_001:50', false)
            ->assertSee('menkyo_original_karimen_expansion_001:26', false);

        Livewire::actingAs($admin)
            ->test(OriginalKarimenPilotReviewIndex::class)
            ->set('filter', 'needs_clarification')
            ->assertSee('menkyo_original_karimen_pilot_001:1', false)
            ->assertSee('menkyo_original_karimen_pilot_001:8', false)
            ->assertSee('menkyo_original_karimen_expansion_001:26', false)
            ->assertSee('menkyo_original_karimen_expansion_001:34', false)
            ->assertSee('menkyo_original_karimen_expansion_001:49', false);
    }

    #[Test]
    public function guests_cannot_open_accuracy_review_and_unauthorized_revision_is_rejected(): void
    {
        $this->stageAllOriginal();
        $entry = QuestionImportEntry::query()
            ->where('source_group', OriginalKarimenExpansionStagingService::SOURCE_GROUP)
            ->where('original_question_number', 26)
            ->firstOrFail();

        $this->get(route('admin.questions.original-karimen-pilot'))
            ->assertRedirect();

        $guest = User::factory()->create();
        $this->actingAs($guest)
            ->get(route('admin.questions.original-karimen-pilot.show', $entry))
            ->assertForbidden();

        $reviews = app(OriginalKarimenPilotAccuracyReviewService::class);
        $revisions = app(OriginalKarimenPilotSourceRevisionService::class);

        try {
            $revisions->submitRevisionDecision($entry, $guest, [
                'decision' => SourceRevisionDecision::ApplyProposedRevision,
                'notes' => 'Guest must not apply expansion source revisions under any circumstance.',
                'expected_content_fingerprint' => $reviews->reviewContentFingerprint($entry),
                'confirm_revision_apply' => true,
            ]);
            $this->fail('Expected validation exception for non-administrator revision submission.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('revision_decision', $exception->errors());
        }
    }

    #[Test]
    public function expansion_clarification_proposals_are_visible_and_remain_unapplied(): void
    {
        $this->stageAllOriginal();
        $admin = User::factory()->administrator()->create();
        $revisions = app(OriginalKarimenPilotSourceRevisionService::class);
        $catalog = $revisions->expansionCatalog();

        foreach ([26, 34, 49] as $number) {
            $entry = QuestionImportEntry::query()
                ->with('translations')
                ->where('source_group', OriginalKarimenExpansionStagingService::SOURCE_GROUP)
                ->where('original_question_number', $number)
                ->firstOrFail();
            $proposal = $revisions->proposalForEntry($entry);
            $this->assertNotNull($proposal);
            $this->assertSame($proposal['current_question_ja'], $entry->translationFor('ja')?->question_text);
            $this->assertNotSame($proposal['proposed_question_ja'], $entry->translationFor('ja')?->question_text);
            $this->assertFalse($revisions->isSourceClarificationResolved($entry));
            $this->assertTrue((bool) $proposal['research_supported']);
            $this->assertSame('pending_human_decision', $catalog['proposals'][$number]['human_revision_decision_status']);

            Livewire::actingAs($admin)
                ->test(OriginalKarimenPilotReviewShow::class, ['entry' => $entry])
                ->assertSee(__('admin.pilot_source_revision_title'), false)
                ->assertSee($proposal['proposed_question_ja'], false)
                ->assertSee((string) $proposal['exact_provision'], false);
        }

        $this->assertSame(0, (int) $catalog['human_revision_decisions_recorded']);
        $this->assertSame(
            0,
            QuestionImportReviewAudit::query()
                ->where('action', OriginalKarimenPilotSourceRevisionService::AUDIT_ACTION)
                ->count(),
        );
    }

    #[Test]
    public function pilot_one_and_eight_task_47_proposals_remain_pending_and_unapplied(): void
    {
        $this->stageAllOriginal();
        $revisions = app(OriginalKarimenPilotSourceRevisionService::class);

        foreach ([1, 8] as $number) {
            $entry = QuestionImportEntry::query()
                ->with('translations')
                ->where('source_group', OriginalKarimenPilotStagingService::SOURCE_GROUP)
                ->where('original_question_number', $number)
                ->firstOrFail();
            $proposal = $revisions->proposalForEntry($entry);
            $this->assertNotNull($proposal);
            $this->assertSame($proposal['current_question_ja'], $entry->translationFor('ja')?->question_text);
            $this->assertFalse($revisions->isSourceClarificationResolved($entry));
        }
    }

    #[Test]
    public function no_human_approvals_or_publication_are_fabricated_and_pools_stay_empty(): void
    {
        $this->stageAllOriginal();

        $entries = QuestionImportEntry::query()
            ->whereIn('source_group', [
                OriginalKarimenPilotStagingService::SOURCE_GROUP,
                OriginalKarimenExpansionStagingService::SOURCE_GROUP,
            ])
            ->get();

        $this->assertCount(50, $entries);
        foreach ($entries as $entry) {
            $this->assertFalse((bool) $entry->is_published);
            $this->assertNull($entry->promoted_question_id);
            $this->assertFalse((bool) data_get($entry->source_metadata, 'human_accuracy_review'));
            $this->assertFalse((bool) data_get($entry->source_metadata, 'human_editorial_approval'));
            $this->assertFalse((bool) data_get($entry->source_metadata, 'publication_eligible'));
            $this->assertFalse((bool) data_get($entry->source_metadata, 'public_exam_eligible'));
        }

        $this->assertSame(
            0,
            QuestionImportEntry::query()
                ->whereIn('source_group', [
                    OriginalKarimenPilotStagingService::SOURCE_GROUP,
                    OriginalKarimenExpansionStagingService::SOURCE_GROUP,
                ])
                ->whereNotNull('promoted_question_id')
                ->count(),
        );
    }

    #[Test]
    public function imported_records_and_pilot_translation_drafts_remain_unchanged(): void
    {
        Artisan::call('menkyo:stage-original-karimen-pilot', ['--commit' => true]);
        Artisan::call('menkyo:stage-original-karimen-pilot-translations', ['--commit' => true]);

        $draftQuery = fn () => QuestionImportEntryTranslation::query()
            ->where('locale', '!=', 'ja')
            ->whereHas('entry', fn ($q) => $q->where('source_group', OriginalKarimenPilotStagingService::SOURCE_GROUP))
            ->where('japanese_reading_data->pilot_translation_meta->drafting_method', OriginalKarimenPilotTranslationDraftService::DRAFTING_METHOD);

        $draftHashesBefore = $draftQuery()
            ->orderBy('id')
            ->get(['id', 'locale', 'question_text', 'explanation', 'status'])
            ->map(fn (QuestionImportEntryTranslation $row): string => hash('sha256', json_encode($row->toArray())))
            ->all();
        $this->assertCount(56, $draftHashesBefore);

        $batch = QuestionImportBatch::factory()->create([
            'source_key' => 'karimen_exam_trainer_en_610',
            'entry_count' => 1,
        ]);
        $sampleImported = QuestionImportEntry::factory()->create([
            'question_import_batch_id' => $batch->id,
            'source_group' => 'karimen_test_1',
            'english_question' => 'IMPORTED STEM MUST STAY PUT FOR TASK 49',
            'original_question_number' => 9991,
        ]);
        $importedHash = hash('sha256', (string) $sampleImported->english_question);

        Artisan::call('menkyo:stage-original-karimen-expansion', ['--commit' => true]);

        $draftHashesAfter = $draftQuery()
            ->orderBy('id')
            ->get(['id', 'locale', 'question_text', 'explanation', 'status'])
            ->map(fn (QuestionImportEntryTranslation $row): string => hash('sha256', json_encode($row->toArray())))
            ->all();
        $this->assertSame($draftHashesBefore, $draftHashesAfter);
        $this->assertSame(
            $importedHash,
            hash('sha256', (string) $sampleImported->fresh()->english_question),
        );

        $eligible = app(OriginalKarimenPilotTranslationDraftService::class)->eligibleCandidateNumbers();
        $this->assertNotContains(1, $eligible);
        $this->assertNotContains(8, $eligible);
    }

    #[Test]
    public function review_packet_document_exists_with_fifty_rows_and_five_proposals(): void
    {
        $path = base_path('docs/KARIMEN_ORIGINAL_50_REVIEW_PACKET.md');
        $this->assertFileExists($path);
        $contents = (string) file_get_contents($path);
        $this->assertTrue(
            str_contains($contents, 'Human-approved count: 0')
            || str_contains($contents, 'Human accuracy accepted: 0'),
        );
        $this->assertStringContainsString('menkyo_original_karimen_expansion_001:26', $contents);
        $this->assertStringContainsString('menkyo_original_karimen_expansion_001:34', $contents);
        $this->assertStringContainsString('menkyo_original_karimen_expansion_001:49', $contents);
        $this->assertStringContainsString('Proposed question (JA):', $contents);
        $this->assertStringContainsString('/admin/questions/original-karimen-pilot', $contents);
        $this->assertSame(50, preg_match_all('/^\| `menkyo_original_karimen_(?:pilot|expansion)_001:\d+`/m', $contents));
    }
}
