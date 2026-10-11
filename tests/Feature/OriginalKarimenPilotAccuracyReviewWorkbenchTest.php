<?php

namespace Tests\Feature;

use App\Enums\AccuracyEvidenceStatus;
use App\Enums\AnswerKeyReviewStatus;
use App\Enums\ContentRightsReviewStatus;
use App\Enums\HumanAccuracyReviewDecision;
use App\Livewire\Admin\OriginalKarimenPilotReviewIndex;
use App\Livewire\Admin\OriginalKarimenPilotReviewShow;
use App\Models\QuestionImportBatch;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportReviewAudit;
use App\Models\User;
use App\Services\Editorial\OriginalKarimenPilotAccuracyReviewService;
use App\Services\Editorial\OriginalKarimenPilotStagingService;
use App\Services\Exams\QuestionSelectionService;
use Database\Seeders\ExamTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OriginalKarimenPilotAccuracyReviewWorkbenchTest extends TestCase
{
    use RefreshDatabase;

    private function stagePilot(): void
    {
        Artisan::call('menkyo:stage-original-karimen-pilot', ['--commit' => true]);
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
    public function guests_cannot_open_or_submit_pilot_accuracy_reviews(): void
    {
        $this->stagePilot();
        $entry = $this->pilotEntry(2);

        $this->get(route('admin.questions.original-karimen-pilot'))
            ->assertRedirect(route('login'));
        $this->get(route('admin.questions.original-karimen-pilot.show', $entry))
            ->assertRedirect(route('login'));

        $this->assertSame(
            0,
            QuestionImportReviewAudit::query()
                ->where('action', OriginalKarimenPilotAccuracyReviewService::AUDIT_ACTION)
                ->count(),
        );
    }

    #[Test]
    public function ordinary_learners_are_denied_pilot_accuracy_review(): void
    {
        $this->stagePilot();
        $entry = $this->pilotEntry(2);
        $learner = User::factory()->create(['is_admin' => false]);

        $this->actingAs($learner)
            ->get(route('admin.questions.original-karimen-pilot'))
            ->assertForbidden();

        Livewire::actingAs($learner)
            ->test(OriginalKarimenPilotReviewShow::class, ['entry' => $entry])
            ->assertForbidden();
    }

    #[Test]
    public function unverified_users_cannot_review_even_if_marked_admin(): void
    {
        $this->stagePilot();
        $entry = $this->pilotEntry(2);
        $unverifiedAdmin = User::factory()->administrator()->unverified()->create();

        $this->actingAs($unverifiedAdmin)
            ->get(route('admin.questions.original-karimen-pilot'))
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($unverifiedAdmin)
            ->get(route('admin.questions.original-karimen-pilot.show', $entry))
            ->assertRedirect(route('verification.notice'));
    }

    #[Test]
    public function only_verified_administrators_may_submit_accuracy_decisions(): void
    {
        $this->stagePilot();
        $entry = $this->pilotEntry(2);
        $admin = User::factory()->administrator()->create();
        $reviews = app(OriginalKarimenPilotAccuracyReviewService::class);
        $fingerprint = $reviews->reviewContentFingerprint($entry);

        Livewire::actingAs($admin)
            ->test(OriginalKarimenPilotReviewShow::class, ['entry' => $entry])
            ->assertSet('decision', null)
            ->set('decision', HumanAccuracyReviewDecision::Deferred->value)
            ->set('notes', 'Test fixture: deferring pending additional statute cross-check.')
            ->set('expectedContentFingerprint', $fingerprint)
            ->call('submitDecision')
            ->assertHasNoErrors();

        $entry->refresh();
        $audit = QuestionImportReviewAudit::query()
            ->where('question_import_entry_id', $entry->id)
            ->where('action', OriginalKarimenPilotAccuracyReviewService::AUDIT_ACTION)
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame($admin->id, $audit->user_id);
        $this->assertNotNull($audit->created_at);
        $this->assertSame(
            HumanAccuracyReviewDecision::Deferred->value,
            data_get($audit->after, 'human_accuracy_decision'),
        );
        $this->assertSame($fingerprint, data_get($audit->after, 'reviewed_content_fingerprint'));
        $this->assertFalse((bool) data_get($audit->after, 'confers_editorial_approval'));
        $this->assertFalse((bool) data_get($audit->after, 'confers_rights_clearance'));
        $this->assertFalse((bool) data_get($audit->after, 'confers_publication'));
    }

    #[Test]
    public function all_ten_pilot_candidates_are_available_privately_on_the_dashboard(): void
    {
        $this->stagePilot();
        $admin = User::factory()->administrator()->create();

        Livewire::actingAs($admin)
            ->test(OriginalKarimenPilotReviewIndex::class)
            ->set('filter', 'all')
            ->assertSee('menkyo_original_karimen_pilot_001:1', false)
            ->assertSee('menkyo_original_karimen_pilot_001:10', false)
            ->assertSee(__('admin.pilot_status_unreviewed'), false);

        $this->assertSame(
            10,
            app(OriginalKarimenPilotAccuracyReviewService::class)->pilotEntriesQuery()->count(),
        );
    }

    #[Test]
    public function imported_bank_questions_cannot_use_the_pilot_review_endpoint(): void
    {
        $this->stagePilot();
        $admin = User::factory()->administrator()->create();
        $batch = QuestionImportBatch::factory()->create([
            'source_key' => 'karimen_exam_trainer_en_610',
            'entry_count' => 1,
        ]);
        $imported = QuestionImportEntry::factory()->create([
            'question_import_batch_id' => $batch->id,
            'source_group' => 'karimen_test_1',
            'original_question_number' => 1,
            'english_question' => 'IMPORTED STEM MUST NOT CHANGE',
        ]);
        $beforeHash = hash('sha256', (string) $imported->english_question);

        $this->actingAs($admin)
            ->get(route('admin.questions.original-karimen-pilot.show', $imported))
            ->assertNotFound();

        $reviews = app(OriginalKarimenPilotAccuracyReviewService::class);
        try {
            $reviews->submitDecision($imported, $admin, [
                'decision' => HumanAccuracyReviewDecision::Accepted,
                'notes' => 'Should not approve imported bank content through pilot workbench.',
                'expected_content_fingerprint' => $reviews->reviewContentFingerprint($imported),
                'confirm_acceptance' => true,
            ]);
            $this->fail('Expected ValidationException for imported bank entries.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('entry', $exception->errors());
        }

        $imported->refresh();
        $this->assertSame($beforeHash, hash('sha256', (string) $imported->english_question));
        $this->assertSame(
            0,
            QuestionImportReviewAudit::query()
                ->where('question_import_entry_id', $imported->id)
                ->where('action', OriginalKarimenPilotAccuracyReviewService::AUDIT_ACTION)
                ->count(),
        );
    }

    #[Test]
    public function imported_source_question_count_fixture_remains_untouched_by_pilot_review(): void
    {
        $batch = QuestionImportBatch::factory()->create([
            'source_key' => 'karimen_exam_trainer_en_610',
            'entry_count' => 1024,
        ]);
        QuestionImportEntry::factory()->count(3)->create([
            'question_import_batch_id' => $batch->id,
            'source_group' => 'karimen_test_1',
        ]);
        $before = QuestionImportEntry::query()->where('source_group', 'karimen_test_1')->get()
            ->map(fn (QuestionImportEntry $e): string => hash('sha256', json_encode([
                $e->id,
                $e->english_question,
                $e->correct_answer,
                $e->is_published,
            ])))
            ->all();

        $this->stagePilot();
        $admin = User::factory()->administrator()->create();
        $entry = $this->pilotEntry(3);
        $reviews = app(OriginalKarimenPilotAccuracyReviewService::class);

        $reviews->submitDecision($entry, $admin, [
            'decision' => HumanAccuracyReviewDecision::RevisionRequested,
            'notes' => 'Test fixture revision request only; do not alter imported bank rows.',
            'expected_content_fingerprint' => $reviews->reviewContentFingerprint($entry),
        ]);

        $after = QuestionImportEntry::query()->where('source_group', 'karimen_test_1')->orderBy('id')->get()
            ->map(fn (QuestionImportEntry $e): string => hash('sha256', json_encode([
                $e->id,
                $e->english_question,
                $e->correct_answer,
                $e->is_published,
            ])))
            ->all();

        $this->assertSame($before, $after);
        $this->assertSame(3, QuestionImportEntry::query()->where('source_group', 'karimen_test_1')->count());
        $this->assertSame(1024, (int) $batch->fresh()->entry_count);
    }

    #[Test]
    public function review_form_has_no_default_decision_and_requires_meaningful_notes(): void
    {
        $this->stagePilot();
        $admin = User::factory()->administrator()->create();
        $entry = $this->pilotEntry(2);

        Livewire::actingAs($admin)
            ->test(OriginalKarimenPilotReviewShow::class, ['entry' => $entry])
            ->assertSet('decision', null)
            ->set('notes', 'short')
            ->call('submitDecision')
            ->assertHasErrors(['decision', 'notes']);
    }

    #[Test]
    public function stale_fingerprint_submissions_are_rejected_safely(): void
    {
        $this->stagePilot();
        $admin = User::factory()->administrator()->create();
        $entry = $this->pilotEntry(2);

        Livewire::actingAs($admin)
            ->test(OriginalKarimenPilotReviewShow::class, ['entry' => $entry])
            ->set('decision', HumanAccuracyReviewDecision::Deferred->value)
            ->set('notes', 'Stale form submission should fail closed with a reload message.')
            ->set('expectedContentFingerprint', str_repeat('a', 64))
            ->call('submitDecision')
            ->assertHasErrors(['expected_content_fingerprint']);

        $this->assertSame(
            0,
            QuestionImportReviewAudit::query()
                ->where('question_import_entry_id', $entry->id)
                ->where('action', OriginalKarimenPilotAccuracyReviewService::AUDIT_ACTION)
                ->count(),
        );
    }

    #[Test]
    public function double_submit_does_not_create_duplicate_approval_audits(): void
    {
        $this->stagePilot();
        $admin = User::factory()->administrator()->create();
        $entry = $this->pilotEntry(2);
        $reviews = app(OriginalKarimenPilotAccuracyReviewService::class);
        $payload = [
            'decision' => HumanAccuracyReviewDecision::Deferred->value,
            'notes' => 'Idempotent double-submit protection for identical review payloads.',
            'expected_content_fingerprint' => $reviews->reviewContentFingerprint($entry),
        ];

        $reviews->submitDecision($entry, $admin, [
            'decision' => HumanAccuracyReviewDecision::Deferred,
            'notes' => $payload['notes'],
            'expected_content_fingerprint' => $payload['expected_content_fingerprint'],
        ]);
        $reviews->submitDecision($entry->fresh(['translations', 'batch']), $admin, [
            'decision' => HumanAccuracyReviewDecision::Deferred,
            'notes' => $payload['notes'],
            'expected_content_fingerprint' => $payload['expected_content_fingerprint'],
        ]);

        $this->assertSame(
            1,
            QuestionImportReviewAudit::query()
                ->where('question_import_entry_id', $entry->id)
                ->where('action', OriginalKarimenPilotAccuracyReviewService::AUDIT_ACTION)
                ->count(),
        );
    }

    #[Test]
    public function previous_review_history_is_preserved_across_later_decisions(): void
    {
        $this->stagePilot();
        $admin = User::factory()->administrator()->create();
        $entry = $this->pilotEntry(2);
        $reviews = app(OriginalKarimenPilotAccuracyReviewService::class);

        $reviews->submitDecision($entry, $admin, [
            'decision' => HumanAccuracyReviewDecision::Deferred,
            'notes' => 'First human decision in the isolated test fixture history.',
            'expected_content_fingerprint' => $reviews->reviewContentFingerprint($entry),
        ]);

        $this->travel(10)->seconds();

        $entry = $entry->fresh(['translations', 'batch']);
        $reviews->submitDecision($entry, $admin, [
            'decision' => HumanAccuracyReviewDecision::RevisionRequested,
            'notes' => 'Second human decision must keep the earlier audit row intact.',
            'expected_content_fingerprint' => $reviews->reviewContentFingerprint($entry),
        ]);

        $history = $reviews->reviewHistory($entry->fresh());
        $this->assertCount(2, $history);
        $this->assertSame(
            HumanAccuracyReviewDecision::RevisionRequested->value,
            data_get($history->first()->after, 'human_accuracy_decision'),
        );
        $this->assertSame(
            HumanAccuracyReviewDecision::Deferred->value,
            data_get($history->last()->after, 'human_accuracy_decision'),
        );
    }

    #[Test]
    public function candidate_one_displays_scope_clarification_and_requires_ack_for_acceptance(): void
    {
        $this->stagePilot();
        $admin = User::factory()->administrator()->create();
        $entry = $this->pilotEntry(1);
        $reviews = app(OriginalKarimenPilotAccuracyReviewService::class);
        $fingerprint = $reviews->reviewContentFingerprint($entry);

        Livewire::actingAs($admin)
            ->test(OriginalKarimenPilotReviewShow::class, ['entry' => $entry])
            ->assertSee(__('admin.pilot_source_revision_title'), false)
            ->assertSee('歩行者等', false)
            ->assertSee(__('admin.pilot_source_issue_unresolved'), false)
            ->set('decision', HumanAccuracyReviewDecision::Accepted->value)
            ->set('notes', 'Accepting after reviewing Article 7 subject scope for pedestrians/etc. and vehicles/etc.')
            ->set('expectedContentFingerprint', $fingerprint)
            ->set('confirmAcceptance', true)
            ->set('acknowledgesScopeNote', false)
            ->call('submitDecision')
            ->assertHasErrors(['acknowledges_scope_note']);
    }

    #[Test]
    public function candidate_eight_displays_proposed_stem_and_streetcar_note(): void
    {
        $this->stagePilot();
        $admin = User::factory()->administrator()->create();
        $entry = $this->pilotEntry(8);

        Livewire::actingAs($admin)
            ->test(OriginalKarimenPilotReviewShow::class, ['entry' => $entry])
            ->assertSee(__('admin.pilot_source_revision_title'), false)
            ->assertSee(__('admin.proposed_question_revision'), false)
            ->assertSee('路面電車', false)
            ->assertSee(__('admin.proposed_revision_not_applied'), false)
            ->assertSee(__('admin.pilot_section_revision_decision'), false);
    }

    #[Test]
    public function revision_requests_do_not_change_candidate_content_automatically(): void
    {
        $this->stagePilot();
        $admin = User::factory()->administrator()->create();
        $entry = $this->pilotEntry(8);
        $beforeStem = $entry->translationFor('ja')?->question_text;
        $proposed = data_get($entry->source_metadata, 'proposed_question_ja');
        $reviews = app(OriginalKarimenPilotAccuracyReviewService::class);

        $reviews->submitDecision($entry, $admin, [
            'decision' => HumanAccuracyReviewDecision::RevisionRequested,
            'notes' => 'Request applying the proposed Article 36 streetcar-aware stem via controlled revision.',
            'expected_content_fingerprint' => $reviews->reviewContentFingerprint($entry),
        ]);

        $entry->refresh()->load('translations');
        $this->assertSame($beforeStem, $entry->translationFor('ja')?->question_text);
        $this->assertNotSame($proposed, $entry->translationFor('ja')?->question_text);
        $this->assertFalse((bool) $entry->is_published);
    }

    #[Test]
    public function material_content_change_invalidates_prior_accuracy_acceptance(): void
    {
        $this->stagePilot();
        $admin = User::factory()->administrator()->create();
        $entry = $this->pilotEntry(2);
        $reviews = app(OriginalKarimenPilotAccuracyReviewService::class);

        $reviews->submitDecision($entry, $admin, [
            'decision' => HumanAccuracyReviewDecision::Accepted,
            'notes' => 'Test acceptance of current fingerprint before a material stem edit.',
            'expected_content_fingerprint' => $reviews->reviewContentFingerprint($entry),
            'confirm_acceptance' => true,
        ]);

        $this->assertTrue($reviews->humanAccuracyAcceptedForCurrentVersion($entry->fresh(['translations', 'batch'])));

        $translation = $entry->fresh(['translations'])->translationFor('ja');
        $this->assertNotNull($translation);
        $translation->forceFill([
            'question_text' => $translation->question_text.'【テスト用の実質変更】',
        ])->save();

        $entry = $entry->fresh(['translations', 'batch']);
        $this->assertFalse($reviews->humanAccuracyAcceptedForCurrentVersion($entry));
        $this->assertTrue((bool) data_get($entry->fresh()->source_metadata, 'human_accuracy_approval_stale'));
        $this->assertSame(
            AccuracyEvidenceStatus::NeedsClarification,
            $entry->fresh()->accuracy_evidence_status,
        );

        $this->assertSame(
            1,
            QuestionImportReviewAudit::query()
                ->where('question_import_entry_id', $entry->id)
                ->where('action', OriginalKarimenPilotAccuracyReviewService::AUDIT_ACTION)
                ->count(),
        );
    }

    #[Test]
    public function accuracy_acceptance_never_grants_editorial_rights_publication_or_promotion(): void
    {
        $this->stagePilot();
        $admin = User::factory()->administrator()->create();
        $entry = $this->pilotEntry(3);
        $reviews = app(OriginalKarimenPilotAccuracyReviewService::class);

        $reviews->submitDecision($entry, $admin, [
            'decision' => HumanAccuracyReviewDecision::Accepted,
            'notes' => 'Accuracy-only acceptance must leave every other publication gate closed.',
            'expected_content_fingerprint' => $reviews->reviewContentFingerprint($entry),
            'confirm_acceptance' => true,
        ]);

        $entry->refresh();
        $this->assertSame(AccuracyEvidenceStatus::HumanReviewed, $entry->accuracy_evidence_status);
        $this->assertFalse((bool) data_get($entry->source_metadata, 'human_editorial_approval'));
        $this->assertNotSame(ContentRightsReviewStatus::ApprovedForPublicReuse, $entry->content_rights_status);
        $this->assertNotSame(AnswerKeyReviewStatus::Verified, $entry->answer_key_status);
        $this->assertFalse((bool) $entry->is_published);
        $this->assertNull($entry->promoted_question_id);
        $this->assertFalse((bool) data_get($entry->source_metadata, 'public_exam_eligible'));
        $this->assertFalse((bool) data_get($entry->source_metadata, 'publication_eligible'));
    }

    #[Test]
    public function all_ten_pilot_candidates_remain_outside_live_pools_and_unpublished(): void
    {
        config(['services.adsense.enabled' => false]);
        $this->seed(ExamTypeSeeder::class);
        $this->stagePilot();
        $admin = User::factory()->administrator()->create();
        $reviews = app(OriginalKarimenPilotAccuracyReviewService::class);

        foreach (range(1, 10) as $number) {
            $entry = $this->pilotEntry($number);
            if ($number === 1) {
                $reviews->submitDecision($entry, $admin, [
                    'decision' => HumanAccuracyReviewDecision::Deferred,
                    'notes' => 'Deferring candidate one in test; publication gates must stay closed.',
                    'expected_content_fingerprint' => $reviews->reviewContentFingerprint($entry),
                ]);
            } elseif ($number === 8) {
                $reviews->submitDecision($entry, $admin, [
                    'decision' => HumanAccuracyReviewDecision::RevisionRequested,
                    'notes' => 'Revision requested for candidate eight; stem must stay private.',
                    'expected_content_fingerprint' => $reviews->reviewContentFingerprint($entry),
                ]);
            } else {
                $reviews->submitDecision($entry, $admin, [
                    'decision' => HumanAccuracyReviewDecision::Accepted,
                    'notes' => "Isolated test accuracy accept for pilot candidate {$number} only.",
                    'expected_content_fingerprint' => $reviews->reviewContentFingerprint($entry),
                    'confirm_acceptance' => true,
                ]);
            }
        }

        $this->assertSame(
            0,
            QuestionImportEntry::query()
                ->where('source_group', OriginalKarimenPilotStagingService::SOURCE_GROUP)
                ->where('is_published', true)
                ->count(),
        );
        $this->assertSame(
            0,
            QuestionImportEntry::query()
                ->where('source_group', OriginalKarimenPilotStagingService::SOURCE_GROUP)
                ->whereNotNull('promoted_question_id')
                ->count(),
        );

        $offer = app(QuestionSelectionService::class)->practiceOffer('karimen', null, 'ja');
        $this->assertFalse($offer['full']['can_start']);

        $this->assertFalse((bool) config('services.adsense.enabled'));
        $this->get(route('home'))->assertSuccessful()->assertDontSee('data-adsense-auto', false);
        $this->get(route('login'))->assertSuccessful();
    }
}
