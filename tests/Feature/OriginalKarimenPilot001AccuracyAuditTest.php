<?php

namespace Tests\Feature;

use App\Enums\AccuracyEvidenceStatus;
use App\Enums\AnswerKeyReviewStatus;
use App\Enums\ContentReviewStatus;
use App\Enums\ContentRightsReviewStatus;
use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Livewire\Admin\QuestionBankShow;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\QuestionImportBatch;
use App\Models\QuestionImportEntry;
use App\Models\User;
use App\Services\Editorial\OriginalKarimenPilotAccuracyAudit;
use App\Services\Editorial\OriginalKarimenPilotStagingService;
use App\Services\Exams\QuestionSelectionService;
use Database\Seeders\ExamTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OriginalKarimenPilot001AccuracyAuditTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_accuracy_audit_covers_stable_ids_without_granting_human_approval(): void
    {
        $audit = app(OriginalKarimenPilotAccuracyAudit::class)->catalog();
        $pilot = app(OriginalKarimenPilotStagingService::class)->catalog();

        $this->assertSame(OriginalKarimenPilotStagingService::COLLECTION_KEY, $audit['collection_key']);
        $this->assertSame(10, (int) $audit['summary']['total_reviewed']);
        $this->assertSame(8, (int) $audit['summary']['supported_as_written']);
        $this->assertSame(2, (int) $audit['summary']['supported_with_clarification']);
        $this->assertSame(0, (int) $audit['summary']['ambiguous_needs_revision']);
        $this->assertSame(0, (int) $audit['summary']['potentially_incorrect']);
        $this->assertSame(0, (int) $audit['summary']['evidence_insufficient']);
        $this->assertSame(0, (int) $audit['summary']['legal_effective_date_unresolved']);

        foreach ($pilot['candidates'] as $candidate) {
            $number = (int) $candidate['number'];
            $row = $audit['candidates'][$number];

            $this->assertSame($candidate['candidate_id'], $row['candidate_id']);
            $this->assertIsBool($candidate['correct_answer']);
            $this->assertNotSame('', trim((string) $candidate['question_ja']));
            $this->assertNotSame('', trim((string) $candidate['explanation_ja']));
            $this->assertNotSame('', trim((string) $candidate['source_url']));
            $this->assertNotSame('', trim((string) $row['exact_provision']));
            $this->assertNotSame('', trim((string) $row['research_assessment']));
            $this->assertFalse(
                app(OriginalKarimenPilotAccuracyAudit::class)
                    ->researchAssessmentConfersHumanApproval((string) $row['research_assessment'])
            );
            $this->assertSame('unpublished', $row['publication_status']);
        }
    }

    #[Test]
    public function test_restaging_attaches_research_metadata_and_keeps_gates_closed(): void
    {
        Artisan::call('menkyo:stage-original-karimen-pilot', ['--commit' => true]);
        Artisan::call('menkyo:stage-original-karimen-pilot', ['--commit' => true]);

        $entries = QuestionImportEntry::query()
            ->where('source_group', OriginalKarimenPilotStagingService::SOURCE_GROUP)
            ->orderBy('original_question_number')
            ->get();

        $this->assertCount(10, $entries);

        foreach ($entries as $entry) {
            $this->assertFalse($entry->is_published);
            $this->assertSame(AnswerKeyReviewStatus::VerificationPending, $entry->answer_key_status);
            $this->assertSame(AccuracyEvidenceStatus::EvidenceCollected, $entry->accuracy_evidence_status);
            $this->assertSame(ContentRightsReviewStatus::Unclear, $entry->content_rights_status);
            $this->assertNull($entry->accuracy_reviewed_at);
            $this->assertFalse((bool) data_get($entry->source_metadata, 'human_accuracy_review'));
            $this->assertFalse((bool) data_get($entry->source_metadata, 'human_editorial_approval'));
            $this->assertFalse((bool) data_get($entry->source_metadata, 'public_exam_eligible'));
            $this->assertFalse((bool) data_get($entry->source_metadata, 'research_assessment_confers_human_approval'));
            $this->assertNotSame('', (string) data_get($entry->source_metadata, 'research_assessment'));
            $this->assertFalse($entry->isPublicationReady());
            $this->assertContains('task_44_accuracy_audited', $entry->review_flags ?? []);
            $this->assertStringContainsString('laws.e-gov.go.jp', (string) data_get($entry->accuracy_evidence_sources, '0.url'));
        }

        $clarification = $entries->firstWhere('original_question_number', 8);
        $this->assertSame('supported_with_clarification', data_get($clarification?->source_metadata, 'research_assessment'));
        $this->assertNotSame('', (string) data_get($clarification?->source_metadata, 'proposed_question_ja'));
        $this->assertContains('needs_editorial_clarification', $clarification?->review_flags ?? []);
    }

    #[Test]
    public function test_ambiguous_research_statuses_cannot_become_publication_ready_via_assessment_alone(): void
    {
        Artisan::call('menkyo:stage-original-karimen-pilot', ['--commit' => true]);

        $entry = QuestionImportEntry::query()
            ->where('source_group', OriginalKarimenPilotStagingService::SOURCE_GROUP)
            ->where('original_question_number', 1)
            ->firstOrFail();

        $metadata = $entry->source_metadata ?? [];
        $metadata['research_assessment'] = 'ambiguous_needs_revision';
        $entry->forceFill([
            'source_metadata' => $metadata,
            'review_flags' => array_values(array_unique([
                ...($entry->review_flags ?? []),
                'research_blocks_auto_publication',
            ])),
            'accuracy_evidence_status' => AccuracyEvidenceStatus::EvidenceCollected,
            'answer_key_status' => AnswerKeyReviewStatus::VerificationPending,
            'is_published' => false,
        ])->save();

        $entry->refresh();
        $this->assertFalse($entry->isPublicationReady());
        $this->assertContains('research_blocks_auto_publication', $entry->review_flags ?? []);
        $this->assertNotSame(AccuracyEvidenceStatus::HumanReviewed, $entry->accuracy_evidence_status);
        $this->assertNotSame(AnswerKeyReviewStatus::Verified, $entry->answer_key_status);
    }

    #[Test]
    public function test_imported_counts_and_exam_pool_exclusion_remain_intact(): void
    {
        QuestionImportBatch::factory()->create([
            'source_key' => 'karimen_exam_trainer_en_610',
            'entry_count' => 2,
        ]);
        QuestionImportEntry::factory()->count(2)->create([
            'question_import_batch_id' => QuestionImportBatch::query()
                ->where('source_key', 'karimen_exam_trainer_en_610')
                ->value('id'),
            'source_group' => 'karimen_test_1',
        ]);

        Artisan::call('menkyo:stage-original-karimen-pilot', ['--commit' => true]);

        $this->assertSame(2, QuestionImportEntry::query()->where('source_group', 'karimen_test_1')->count());
        $this->assertSame(
            10,
            QuestionImportEntry::query()->where('source_group', OriginalKarimenPilotStagingService::SOURCE_GROUP)->count(),
        );
        $this->assertSame([], app(OriginalKarimenPilotAccuracyAudit::class)->similarityFlags());

        $pilotTexts = collect(app(OriginalKarimenPilotStagingService::class)->catalog()['candidates'])
            ->pluck('question_ja')
            ->all();

        $this->assertSame(
            0,
            Question::query()->whereIn('japanese_text', $pilotTexts)->where('status', QuestionStatus::Published)->count(),
        );

        $examType = ExamType::factory()->create(['slug' => 'karimen-audit-pool', 'is_active' => true]);
        $published = Question::factory()->create([
            'status' => QuestionStatus::Published,
            'content_review_status' => ContentReviewStatus::Reviewed,
            'is_demo' => false,
            'type' => QuestionType::StandardTrueFalse,
            'japanese_text' => 'NON PILOT PUBLISHED',
        ]);
        $published->examTypes()->attach($examType->id);

        $availability = app(QuestionSelectionService::class)->fullAvailability($examType, 'ja');
        $this->assertGreaterThanOrEqual(1, $availability->availableStandard);
    }

    #[Test]
    public function test_admin_shows_research_assessment_and_guests_remain_forbidden(): void
    {
        Artisan::call('menkyo:stage-original-karimen-pilot', ['--commit' => true]);
        $entry = QuestionImportEntry::query()
            ->where('source_group', OriginalKarimenPilotStagingService::SOURCE_GROUP)
            ->where('original_question_number', 8)
            ->firstOrFail();

        $guest = User::factory()->create(['is_admin' => false]);
        $this->actingAs($guest)
            ->get(route('admin.questions.show', $entry))
            ->assertForbidden();

        $admin = User::factory()->administrator()->create();
        Livewire::actingAs($admin)
            ->test(QuestionBankShow::class, ['entry' => $entry->fresh(['translations', 'batch'])])
            ->assertOk()
            ->assertSee('supported_with_clarification', false)
            ->assertSee(__('admin.research_assessment_not_human_approval', [], 'ja'), false)
            ->assertSee('laws.e-gov.go.jp', false)
            ->assertSee((string) data_get($entry->source_metadata, 'proposed_question_ja'), false);
    }

    #[Test]
    public function test_human_protected_entries_are_not_overwritten_and_demo_offer_still_works(): void
    {
        $this->seed(ExamTypeSeeder::class);
        config(['services.adsense.enabled' => false]);

        Artisan::call('menkyo:stage-original-karimen-pilot', ['--commit' => true]);

        $entry = QuestionImportEntry::query()
            ->where('source_group', OriginalKarimenPilotStagingService::SOURCE_GROUP)
            ->where('original_question_number', 2)
            ->firstOrFail();

        $entry->forceFill([
            'answer_key_status' => AnswerKeyReviewStatus::Verified,
            'answer_key_verified_at' => now(),
            'english_question' => 'HUMAN PROTECTED GLOSS',
            'source_metadata' => array_merge($entry->source_metadata ?? [], [
                'research_assessment' => 'human_locked_fixture',
            ]),
        ])->save();

        Artisan::call('menkyo:stage-original-karimen-pilot', ['--commit' => true]);
        $entry->refresh();

        $this->assertSame('HUMAN PROTECTED GLOSS', $entry->english_question);
        $this->assertSame('human_locked_fixture', data_get($entry->source_metadata, 'research_assessment'));
        $this->assertSame(AnswerKeyReviewStatus::Verified, $entry->answer_key_status);

        $offer = app(QuestionSelectionService::class)->practiceOffer('karimen', null, 'ja');
        $this->assertTrue($offer['configured']);
        $this->assertFalse($offer['full']['can_start']);
        $this->assertFalse((bool) config('services.adsense.enabled'));
    }
}
