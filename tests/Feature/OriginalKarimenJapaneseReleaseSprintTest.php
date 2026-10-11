<?php

namespace Tests\Feature;

use App\Enums\AnswerKeyReviewStatus;
use App\Enums\ContentReviewStatus;
use App\Enums\ExamAttemptType;
use App\Enums\HumanAccuracyReviewDecision;
use App\Enums\QuestionStatus;
use App\Enums\SourceRevisionDecision;
use App\Enums\StagedTranslationStatus;
use App\Livewire\Admin\OriginalKarimenPilotReviewShow;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportEntryTranslation;
use App\Models\QuestionImportReviewAudit;
use App\Models\User;
use App\Services\Editorial\KarimenReleaseReadinessService;
use App\Services\Editorial\OriginalKarimenPilotAccuracyReviewService;
use App\Services\Editorial\OriginalKarimenPilotSourceRevisionService;
use App\Services\Editorial\OriginalKarimenPilotStagingService;
use App\Services\Editorial\OriginalKarimenPilotTranslationDraftService;
use App\Services\Editorial\OriginalKarimenReleaseGateService;
use App\Services\Exams\ExamAttemptService;
use App\Services\Exams\QuestionSelectionService;
use App\Services\Translations\StagedQuestionPublicationService;
use Database\Seeders\ExamTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OriginalKarimenJapaneseReleaseSprintTest extends TestCase
{
    use RefreshDatabase;

    private function stageAll(): void
    {
        $this->seed(ExamTypeSeeder::class);
        Artisan::call('menkyo:stage-original-karimen-pilot', ['--commit' => true]);
        Artisan::call('menkyo:stage-original-karimen-pilot-translations', ['--commit' => true]);
        Artisan::call('menkyo:stage-original-karimen-expansion', ['--commit' => true]);
    }

    private function admin(): User
    {
        return User::factory()->administrator()->create();
    }

    private function entry(int $number): QuestionImportEntry
    {
        $group = $number <= 10
            ? OriginalKarimenPilotStagingService::SOURCE_GROUP
            : 'menkyo_original_karimen_expansion_001';

        return QuestionImportEntry::query()
            ->with(['translations', 'batch'])
            ->where('source_group', $group)
            ->where('original_question_number', $number)
            ->firstOrFail();
    }

    private function acceptAccuracy(QuestionImportEntry $entry, User $admin): QuestionImportEntry
    {
        $reviews = app(OriginalKarimenPilotAccuracyReviewService::class);
        $fingerprint = $reviews->reviewContentFingerprint($entry);

        return $reviews->submitDecision($entry, $admin, [
            'decision' => HumanAccuracyReviewDecision::Accepted,
            'notes' => 'Human accuracy acceptance for current version with statute evidence reviewed.',
            'expected_content_fingerprint' => $fingerprint,
            'confirm_acceptance' => true,
            'acknowledges_scope_note' => true,
            'acknowledges_proposed_stem' => true,
        ]);
    }

    private function clearAllGates(QuestionImportEntry $entry, User $admin): QuestionImportEntry
    {
        $gates = app(OriginalKarimenReleaseGateService::class);
        $reviews = app(OriginalKarimenPilotAccuracyReviewService::class);
        $revisions = app(OriginalKarimenPilotSourceRevisionService::class);

        if ($revisions->hasProposal($entry) && ! $revisions->isSourceClarificationResolved($entry)) {
            $entry = $revisions->submitRevisionDecision($entry, $admin, [
                'decision' => SourceRevisionDecision::ApplyProposedRevision,
                'notes' => 'Applying research-backed source revision after human legal comparison.',
                'expected_content_fingerprint' => $reviews->reviewContentFingerprint($entry),
                'confirm_revision_apply' => true,
            ]);
        }

        $entry = $this->acceptAccuracy($entry->fresh(['translations', 'batch']), $admin);
        $fingerprint = $reviews->reviewContentFingerprint($entry);

        $entry = $gates->submitEditorialDecision($entry, $admin, [
            'approved' => true,
            'notes' => 'Editorial approval for current Japanese wording and explanation.',
            'expected_content_fingerprint' => $fingerprint,
            'confirm_approval' => true,
        ]);
        $fingerprint = $reviews->reviewContentFingerprint($entry);

        $entry = $gates->submitAnswerKeyDecision($entry, $admin, [
            'verified' => true,
            'notes' => 'Answer key verified against cited Road Traffic Act provision.',
            'reference' => (string) $entry->answer_key_reference,
            'expected_content_fingerprint' => $fingerprint,
        ]);
        $fingerprint = $reviews->reviewContentFingerprint($entry);

        $entry = $gates->submitRightsDecision($entry, $admin, [
            'cleared' => true,
            'notes' => 'MenkyoNavi first-party original content cleared for public reuse.',
            'evidence' => 'Original MenkyoNavi Japanese draft with statute citations; not imported HTML bank.',
            'expected_content_fingerprint' => $fingerprint,
        ]);
        $fingerprint = $reviews->reviewContentFingerprint($entry);

        return $gates->markJapaneseReadyForPublication($entry, $admin, [
            'notes' => 'Japanese stem and explanation ready for publication after human review.',
            'expected_content_fingerprint' => $fingerprint,
            'confirm_ready' => true,
        ]);
    }

    #[Test]
    public function release_gates_are_available_on_review_show_and_remain_separate(): void
    {
        $this->stageAll();
        $admin = $this->admin();
        $entry = $this->entry(2);

        Livewire::actingAs($admin)
            ->test(OriginalKarimenPilotReviewShow::class, ['entry' => $entry])
            ->assertSee(__('admin.pilot_release_actions_title'))
            ->assertSee(__('admin.pilot_gate_editorial'))
            ->assertSee(__('admin.publish_to_live_pool'));

        $gates = app(OriginalKarimenReleaseGateService::class);
        $fingerprint = app(OriginalKarimenPilotAccuracyReviewService::class)->reviewContentFingerprint($entry);

        $entry = $gates->submitEditorialDecision($entry, $admin, [
            'approved' => true,
            'notes' => 'Editorial approval only; accuracy not implied by this action.',
            'expected_content_fingerprint' => $fingerprint,
            'confirm_approval' => true,
        ]);

        $this->assertTrue($gates->editorialApprovedForCurrentVersion($entry));
        $this->assertFalse(app(OriginalKarimenPilotAccuracyReviewService::class)->humanAccuracyAcceptedForCurrentVersion($entry));
        $this->assertSame(AnswerKeyReviewStatus::VerificationPending, $entry->answer_key_status);
        $this->assertSame(
            1,
            QuestionImportReviewAudit::query()
                ->where('question_import_entry_id', $entry->id)
                ->where('action', OriginalKarimenReleaseGateService::EDITORIAL_AUDIT_ACTION)
                ->count(),
        );
    }

    #[Test]
    public function informational_review_flags_do_not_block_publication_after_gates_pass(): void
    {
        $this->stageAll();
        $admin = $this->admin();
        $entry = $this->clearAllGates($this->entry(2), $admin);

        $blockers = app(StagedQuestionPublicationService::class)->publicationGateBlockers($entry);
        $this->assertSame([], $blockers, implode(', ', $blockers));
        $this->assertContains('original_menkyo_pilot', $entry->review_flags ?? []);
        $this->assertSame([], $entry->blockingReviewFlags());
    }

    #[Test]
    public function eligible_fixture_publishes_into_japanese_karimen_pool_and_can_start_full_exam(): void
    {
        $this->stageAll();
        $admin = $this->admin();
        $karimen = ExamType::query()->where('slug', 'karimen')->firstOrFail();

        for ($number = 1; $number <= 50; $number++) {
            $entry = $this->clearAllGates($this->entry($number), $admin);
            $result = app(StagedQuestionPublicationService::class)->publishToLivePool(
                $entry->fresh(['translations', 'batch']),
                $admin,
            );
            $this->assertTrue($result['published'], 'Failed publishing #'.$number.': '.($result['message'] ?? ''));
        }

        $this->assertSame(50, Question::query()->where('status', QuestionStatus::Published)->where('is_demo', false)->count());
        $availability = app(QuestionSelectionService::class)->fullAvailability($karimen, 'ja');
        $this->assertTrue($availability->canStart);
        $this->assertSame(50, $availability->availableStandard);

        $learner = User::factory()->create(['is_admin' => false]);
        $attempt = app(ExamAttemptService::class)->start($learner, $karimen, ExamAttemptType::Full);
        $this->assertSame(50, $attempt->question_count);
        $this->assertTrue($attempt->counts_toward_analytics);
        $this->assertSame(ExamAttemptType::Full, $attempt->attempt_type);

        $report = app(KarimenReleaseReadinessService::class)->report();
        $this->assertSame(50, $report['published_count']);
        $this->assertTrue($report['japanese_public_karimen_can_start']);
    }

    #[Test]
    public function unapproved_questions_cannot_publish_and_release_check_stays_read_only(): void
    {
        $this->stageAll();
        $admin = $this->admin();
        $entry = $this->entry(3);

        $result = app(StagedQuestionPublicationService::class)->publishToLivePool($entry, $admin);
        $this->assertFalse($result['published']);
        $this->assertNotEmpty($result['blockers']);
        $this->assertSame(0, Question::query()->where('status', QuestionStatus::Published)->count());

        $checksum = fn (): string => hash('sha256', QuestionImportEntry::query()
            ->orderBy('id')
            ->get(['id', 'updated_at', 'is_published', 'answer_key_status', 'content_rights_status', 'accuracy_evidence_status', 'publication_blockers', 'source_metadata', 'review_flags'])
            ->toJson());
        $before = $checksum();
        $this->artisan('menkyo:karimen-release-check')->assertSuccessful();
        $this->assertSame($before, $checksum());

        $report = app(KarimenReleaseReadinessService::class)->report();
        $this->assertSame(0, $report['human_accuracy_accepted_count']);
        $this->assertSame(0, $report['published_count']);
        $this->assertFalse($report['japanese_public_karimen_can_start']);
    }

    #[Test]
    public function rejecting_source_revision_resolves_clarification_without_changing_wording(): void
    {
        $this->stageAll();
        $admin = $this->admin();
        $entry = $this->entry(1);
        $reviews = app(OriginalKarimenPilotAccuracyReviewService::class);
        $revisions = app(OriginalKarimenPilotSourceRevisionService::class);
        $before = (string) $entry->translationFor('ja')?->question_text;

        $entry = $revisions->submitRevisionDecision($entry, $admin, [
            'decision' => SourceRevisionDecision::RejectProposedRevision,
            'notes' => 'Keeping current wording after human comparison with Article 7 text.',
            'expected_content_fingerprint' => $reviews->reviewContentFingerprint($entry),
            'confirm_revision_apply' => false,
        ]);

        $this->assertTrue($revisions->isSourceClarificationResolved($entry));
        $this->assertSame($before, (string) $entry->fresh(['translations'])->translationFor('ja')?->question_text);
        $this->assertNotContains('needs_editorial_clarification', $entry->review_flags ?? []);
    }

    #[Test]
    public function japanese_ready_requires_confirmation_and_does_not_publish(): void
    {
        $this->stageAll();
        $admin = $this->admin();
        $entry = $this->entry(4);
        $gates = app(OriginalKarimenReleaseGateService::class);
        $fingerprint = app(OriginalKarimenPilotAccuracyReviewService::class)->reviewContentFingerprint($entry);

        $this->expectException(ValidationException::class);
        $gates->markJapaneseReadyForPublication($entry, $admin, [
            'notes' => 'Trying to mark Japanese ready without explicit confirmation checkbox.',
            'expected_content_fingerprint' => $fingerprint,
            'confirm_ready' => false,
        ]);
    }

    #[Test]
    public function adsense_remains_disabled_and_translation_draft_count_is_unchanged(): void
    {
        $this->stageAll();
        $this->assertFalse((bool) config('ads.enabled'));

        $drafts = QuestionImportEntryTranslation::query()
            ->whereHas('entry', fn ($q) => $q->where('source_group', OriginalKarimenPilotStagingService::SOURCE_GROUP))
            ->where('locale', '!=', 'ja')
            ->where(
                'japanese_reading_data->pilot_translation_meta->drafting_method',
                OriginalKarimenPilotTranslationDraftService::DRAFTING_METHOD,
            )
            ->count();

        $this->assertSame(56, $drafts);
        $this->assertSame(
            0,
            Question::query()->where('status', QuestionStatus::Published)->where('content_review_status', ContentReviewStatus::Reviewed)->count(),
        );
        $this->assertSame(StagedTranslationStatus::Draft, $this->entry(2)->translationFor('ja')?->status);
    }
}
