<?php

namespace Tests\Feature;

use App\Enums\AccuracyEvidenceStatus;
use App\Enums\AnswerKeyReviewStatus;
use App\Enums\ContentRightsReviewStatus;
use App\Enums\HumanAccuracyReviewDecision;
use App\Enums\SourceRevisionDecision;
use App\Livewire\Admin\OriginalKarimenPilotReviewShow;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportEntryTranslation;
use App\Models\QuestionImportReviewAudit;
use App\Models\User;
use App\Services\Editorial\OriginalKarimenPilotAccuracyReviewService;
use App\Services\Editorial\OriginalKarimenPilotSourceRevisionService;
use App\Services\Editorial\OriginalKarimenPilotStagingService;
use App\Services\Editorial\OriginalKarimenPilotTranslationDraftService;
use App\Services\Exams\QuestionSelectionService;
use Database\Seeders\ExamTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OriginalKarimenPilotSourceRevisionTest extends TestCase
{
    use RefreshDatabase;

    private function stagePilot(): void
    {
        Artisan::call('menkyo:stage-original-karimen-pilot', ['--commit' => true]);
    }

    private function stageTranslations(): void
    {
        Artisan::call('menkyo:stage-original-karimen-pilot-translations', ['--commit' => true]);
    }

    private function pilotEntry(int $number): QuestionImportEntry
    {
        return QuestionImportEntry::query()
            ->with(['translations', 'batch'])
            ->where('source_group', OriginalKarimenPilotStagingService::SOURCE_GROUP)
            ->where('original_question_number', $number)
            ->firstOrFail();
    }

    /**
     * @return array{question: string, explanation: string, answer: bool, fingerprint: string}
     */
    private function snapshot(QuestionImportEntry $entry): array
    {
        $reviews = app(OriginalKarimenPilotAccuracyReviewService::class);
        $japanese = $entry->translationFor('ja');

        return [
            'question' => (string) $japanese?->question_text,
            'explanation' => (string) $japanese?->explanation,
            'answer' => (bool) $entry->correct_answer,
            'fingerprint' => $reviews->reviewContentFingerprint($entry),
        ];
    }

    #[Test]
    public function candidates_one_and_eight_retain_current_content_before_human_revision_action(): void
    {
        $this->stagePilot();
        $revisions = app(OriginalKarimenPilotSourceRevisionService::class);
        $catalog = $revisions->catalog();

        foreach ([1, 8] as $number) {
            $entry = $this->pilotEntry($number);
            $proposal = $revisions->proposalForNumber($number);
            $this->assertNotNull($proposal);
            $this->assertSame($proposal['current_question_ja'], $entry->translationFor('ja')?->question_text);
            $this->assertSame($proposal['current_correct_answer'], $entry->correct_answer);
            $this->assertNotSame($proposal['proposed_question_ja'], $entry->translationFor('ja')?->question_text);
            $this->assertFalse($revisions->isSourceClarificationResolved($entry));
            $this->assertSame('pending_human_decision', $catalog['proposals'][$number]['human_revision_decision_status']);
            $this->assertTrue((bool) $proposal['research_supported']);
            $this->assertStringContainsString('laws.e-gov.go.jp', (string) $proposal['source_url']);
            $this->assertNotSame('', (string) $proposal['statute_excerpt']);
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
    public function revision_proposals_are_visible_with_source_evidence_and_no_default_decision(): void
    {
        $this->stagePilot();
        $admin = User::factory()->administrator()->create();
        $entry = $this->pilotEntry(8);

        Livewire::actingAs($admin)
            ->test(OriginalKarimenPilotReviewShow::class, ['entry' => $entry])
            ->assertSee(__('admin.pilot_source_revision_title'), false)
            ->assertSee(__('admin.proposed_question_revision'), false)
            ->assertSee('路面電車', false)
            ->assertSee('第36条', false)
            ->assertSee(__('admin.pilot_translation_eligibility_withheld_pending_source_revision'), false)
            ->assertSet('revisionDecision', null)
            ->assertSet('decision', null);
    }

    #[Test]
    public function guests_and_learners_cannot_submit_source_revision_decisions(): void
    {
        $this->stagePilot();
        $entry = $this->pilotEntry(1);
        $learner = User::factory()->create(['is_admin' => false]);

        $this->get(route('admin.questions.original-karimen-pilot.show', $entry))
            ->assertRedirect(route('login'));

        Livewire::actingAs($learner)
            ->test(OriginalKarimenPilotReviewShow::class, ['entry' => $entry])
            ->assertForbidden();

        $this->assertSame(
            0,
            QuestionImportReviewAudit::query()
                ->where('action', OriginalKarimenPilotSourceRevisionService::AUDIT_ACTION)
                ->count(),
        );
    }

    #[Test]
    public function revision_apply_requires_confirmation_rationale_and_records_authenticated_reviewer(): void
    {
        $this->stagePilot();
        $admin = User::factory()->administrator()->create();
        $entry = $this->pilotEntry(1);
        $before = $this->snapshot($entry);
        $revisions = app(OriginalKarimenPilotSourceRevisionService::class);
        $proposal = $revisions->proposalForNumber(1);
        $this->assertNotNull($proposal);

        Livewire::actingAs($admin)
            ->test(OriginalKarimenPilotReviewShow::class, ['entry' => $entry])
            ->set('revisionDecision', SourceRevisionDecision::ApplyProposedRevision->value)
            ->set('revisionNotes', 'Applying Art. 7 subject-scope stem after e-Gov comparison.')
            ->set('expectedContentFingerprint', $before['fingerprint'])
            ->set('confirmRevisionApply', false)
            ->call('submitRevisionDecision')
            ->assertHasErrors(['confirm_revision_apply']);

        try {
            $revisions->submitRevisionDecision($entry->fresh(['translations', 'batch']), $admin, [
                'decision' => SourceRevisionDecision::ApplyProposedRevision,
                'notes' => 'short',
                'expected_content_fingerprint' => $before['fingerprint'],
                'confirm_revision_apply' => true,
            ]);
            $this->fail('Expected short notes validation failure.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('revision_notes', $exception->errors());
        }

        $updated = $revisions->submitRevisionDecision($entry->fresh(['translations', 'batch']), $admin, [
            'decision' => SourceRevisionDecision::ApplyProposedRevision,
            'notes' => 'Applying Art. 7 subject-scope stem after e-Gov comparison.',
            'expected_content_fingerprint' => $before['fingerprint'],
            'confirm_revision_apply' => true,
        ]);

        $this->assertSame($proposal['proposed_question_ja'], $updated->translationFor('ja')?->question_text);
        $this->assertSame(
            $admin->id,
            QuestionImportReviewAudit::query()
                ->where('question_import_entry_id', $updated->id)
                ->where('action', OriginalKarimenPilotSourceRevisionService::AUDIT_ACTION)
                ->value('user_id'),
        );
    }

    #[Test]
    public function applying_revision_updates_japanese_keeps_history_changes_fingerprint_and_invalidates_accuracy(): void
    {
        $this->stagePilot();
        $admin = User::factory()->administrator()->create();
        $entry = $this->pilotEntry(8);
        $reviews = app(OriginalKarimenPilotAccuracyReviewService::class);
        $revisions = app(OriginalKarimenPilotSourceRevisionService::class);
        $proposal = $revisions->proposalForNumber(8);
        $this->assertNotNull($proposal);

        $reviews->submitDecision($entry, $admin, [
            'decision' => HumanAccuracyReviewDecision::Accepted,
            'notes' => 'Temporary fixture acceptance before controlled source revision apply.',
            'expected_content_fingerprint' => $reviews->reviewContentFingerprint($entry),
            'confirm_acceptance' => true,
            'acknowledges_proposed_stem' => true,
        ]);
        $this->assertTrue($reviews->humanAccuracyAcceptedForCurrentVersion($entry->fresh(['translations', 'batch'])));

        $before = $this->snapshot($entry->fresh(['translations', 'batch']));

        $updated = $revisions->submitRevisionDecision($entry->fresh(['translations', 'batch']), $admin, [
            'decision' => SourceRevisionDecision::ApplyProposedRevision,
            'notes' => 'Apply Art. 36(1)(i) streetcar-aware stem after comparing e-Gov text.',
            'expected_content_fingerprint' => $before['fingerprint'],
            'confirm_revision_apply' => true,
        ]);

        $this->assertSame($proposal['proposed_question_ja'], $updated->translationFor('ja')?->question_text);
        $this->assertSame($proposal['proposed_explanation_ja'], $updated->translationFor('ja')?->explanation);
        $this->assertTrue((bool) $updated->correct_answer);
        $this->assertNotSame($before['fingerprint'], $reviews->reviewContentFingerprint($updated));
        $this->assertTrue($revisions->isSourceClarificationResolved($updated));
        $this->assertFalse($reviews->humanAccuracyAcceptedForCurrentVersion($updated));
        $this->assertFalse((bool) data_get($updated->source_metadata, 'human_accuracy_review'));
        $this->assertFalse((bool) data_get($updated->source_metadata, 'human_editorial_approval'));
        $this->assertNotSame(ContentRightsReviewStatus::ApprovedForPublicReuse, $updated->content_rights_status);
        $this->assertNotSame(AnswerKeyReviewStatus::Verified, $updated->answer_key_status);
        $this->assertFalse((bool) $updated->is_published);
        $this->assertNull($updated->promoted_question_id);
        $this->assertSame(AccuracyEvidenceStatus::NeedsClarification, $updated->accuracy_evidence_status);

        $audit = QuestionImportReviewAudit::query()
            ->where('question_import_entry_id', $updated->id)
            ->where('action', OriginalKarimenPilotSourceRevisionService::AUDIT_ACTION)
            ->latest('id')
            ->first();
        $this->assertNotNull($audit);
        $this->assertSame($admin->id, $audit->user_id);
        $this->assertSame($before['question'], data_get($audit->before, 'question_ja'));
        $this->assertSame($proposal['proposed_question_ja'], data_get($audit->after, 'question_ja'));
        $this->assertFalse((bool) data_get($audit->after, 'confers_accuracy_approval'));
        $this->assertFalse((bool) data_get($audit->after, 'confers_publication'));
        $this->assertTrue((bool) data_get($audit->after, 'previous_question_retained_in_history'));
        $this->assertSame(
            $before['question'],
            data_get($updated->source_metadata, 'source_revision_history.0.previous_question_ja'),
        );
    }

    #[Test]
    public function stale_revision_requests_are_rejected_and_duplicates_are_safe(): void
    {
        $this->stagePilot();
        $admin = User::factory()->administrator()->create();
        $entry = $this->pilotEntry(1);
        $revisions = app(OriginalKarimenPilotSourceRevisionService::class);
        $fingerprint = app(OriginalKarimenPilotAccuracyReviewService::class)->reviewContentFingerprint($entry);

        try {
            $revisions->submitRevisionDecision($entry, $admin, [
                'decision' => SourceRevisionDecision::DeferPendingEvidence,
                'notes' => 'Deferring while waiting for additional statutory cross-check notes.',
                'expected_content_fingerprint' => str_repeat('a', 64),
            ]);
            $this->fail('Expected stale fingerprint validation failure.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('expected_content_fingerprint', $exception->errors());
        }

        $first = $revisions->submitRevisionDecision($entry, $admin, [
            'decision' => SourceRevisionDecision::RequestAdditionalChanges,
            'notes' => 'Request a shorter beginner-friendly Art. 7 subject-scope paraphrase.',
            'expected_content_fingerprint' => $fingerprint,
        ]);
        $second = $revisions->submitRevisionDecision($first, $admin, [
            'decision' => SourceRevisionDecision::RequestAdditionalChanges,
            'notes' => 'Request a shorter beginner-friendly Art. 7 subject-scope paraphrase.',
            'expected_content_fingerprint' => $fingerprint,
        ]);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(
            1,
            QuestionImportReviewAudit::query()
                ->where('question_import_entry_id', $entry->id)
                ->where('action', OriginalKarimenPilotSourceRevisionService::AUDIT_ACTION)
                ->count(),
        );
        $this->assertSame($this->snapshot($entry)['question'], $second->translationFor('ja')?->question_text);
        $this->assertFalse($revisions->isSourceClarificationResolved($second));
    }

    #[Test]
    public function withheld_candidates_and_existing_translation_drafts_remain_unchanged_until_apply(): void
    {
        $this->stagePilot();
        $this->stageTranslations();
        $drafts = app(OriginalKarimenPilotTranslationDraftService::class);
        $revisions = app(OriginalKarimenPilotSourceRevisionService::class);

        $draftQuery = QuestionImportEntryTranslation::query()
            ->where('locale', '!=', 'ja')
            ->whereHas('entry', fn ($q) => $q->where('source_group', OriginalKarimenPilotStagingService::SOURCE_GROUP))
            ->where('japanese_reading_data->pilot_translation_meta->drafting_method', OriginalKarimenPilotTranslationDraftService::DRAFTING_METHOD)
            ->orderBy('id');

        $beforeHashes = (clone $draftQuery)
            ->get(['id', 'locale', 'question_text', 'explanation', 'status', 'japanese_reading_data'])
            ->map(fn (QuestionImportEntryTranslation $row): string => hash('sha256', json_encode($row->toArray())))
            ->all();

        $this->assertCount(56, $beforeHashes);
        $this->assertSame(2, count($drafts->withheldCandidates()));
        $this->assertSame(8, count($drafts->eligibleCandidateNumbers()));
        $this->assertNotContains(1, $drafts->eligibleCandidateNumbers());
        $this->assertNotContains(8, $drafts->eligibleCandidateNumbers());

        $admin = User::factory()->administrator()->create();
        $entryOne = $this->pilotEntry(1);
        $revisions->submitRevisionDecision($entryOne, $admin, [
            'decision' => SourceRevisionDecision::DeferPendingEvidence,
            'notes' => 'Defer candidate one; drafts for other candidates must stay untouched.',
            'expected_content_fingerprint' => app(OriginalKarimenPilotAccuracyReviewService::class)->reviewContentFingerprint($entryOne),
        ]);

        $afterHashes = (clone $draftQuery)
            ->get(['id', 'locale', 'question_text', 'explanation', 'status', 'japanese_reading_data'])
            ->map(fn (QuestionImportEntryTranslation $row): string => hash('sha256', json_encode($row->toArray())))
            ->all();

        $this->assertSame($beforeHashes, $afterHashes);
        $this->assertSame(2, count($drafts->withheldCandidates()));
    }

    #[Test]
    public function applying_revision_makes_candidate_draft_eligible_without_auto_translating_or_publishing(): void
    {
        $this->stagePilot();
        $this->stageTranslations();
        $admin = User::factory()->administrator()->create();
        $drafts = app(OriginalKarimenPilotTranslationDraftService::class);
        $revisions = app(OriginalKarimenPilotSourceRevisionService::class);
        $reviews = app(OriginalKarimenPilotAccuracyReviewService::class);

        $draftCount = fn (): int => QuestionImportEntryTranslation::query()
            ->where('locale', '!=', 'ja')
            ->whereHas('entry', fn ($q) => $q->where('source_group', OriginalKarimenPilotStagingService::SOURCE_GROUP))
            ->where('japanese_reading_data->pilot_translation_meta->drafting_method', OriginalKarimenPilotTranslationDraftService::DRAFTING_METHOD)
            ->count();

        $this->assertSame(56, $draftCount());

        $entry = $this->pilotEntry(8);
        $updated = $revisions->submitRevisionDecision($entry, $admin, [
            'decision' => SourceRevisionDecision::ApplyProposedRevision,
            'notes' => 'Apply streetcar-aware stem so later drafting can target the resolved source version.',
            'expected_content_fingerprint' => $reviews->reviewContentFingerprint($entry),
            'confirm_revision_apply' => true,
        ]);

        $this->assertTrue($revisions->isSourceClarificationResolved($updated));
        $this->assertContains(8, $drafts->eligibleCandidateNumbers());
        $this->assertNotContains(1, $drafts->eligibleCandidateNumbers());
        $this->assertCount(1, $drafts->withheldCandidates());
        $this->assertSame(56, $draftCount());
        $this->assertFalse((bool) $updated->is_published);
        $this->assertFalse($reviews->humanAccuracyAcceptedForCurrentVersion($updated));
    }

    #[Test]
    public function imported_banks_demo_pools_and_ads_remain_unaffected(): void
    {
        config(['services.adsense.enabled' => false]);
        $this->seed(ExamTypeSeeder::class);
        $this->stagePilot();

        $importedCountBefore = QuestionImportEntry::query()
            ->where('source_group', '!=', OriginalKarimenPilotStagingService::SOURCE_GROUP)
            ->count();

        $admin = User::factory()->administrator()->create();
        $revisions = app(OriginalKarimenPilotSourceRevisionService::class);
        $entry = $this->pilotEntry(1);
        $revisions->submitRevisionDecision($entry, $admin, [
            'decision' => SourceRevisionDecision::RejectProposedRevision,
            'notes' => 'Reject proposal in fixture; imported banks and pools must remain unchanged.',
            'expected_content_fingerprint' => app(OriginalKarimenPilotAccuracyReviewService::class)->reviewContentFingerprint($entry),
        ]);

        $this->assertSame(
            $importedCountBefore,
            QuestionImportEntry::query()
                ->where('source_group', '!=', OriginalKarimenPilotStagingService::SOURCE_GROUP)
                ->count(),
        );

        $offer = app(QuestionSelectionService::class)->practiceOffer('karimen', null, 'ja');
        $this->assertFalse($offer['full']['can_start']);
        $this->assertFalse((bool) config('services.adsense.enabled'));
        $this->get(route('home'))->assertSuccessful()->assertDontSee('data-adsense-auto', false);
    }
}
