<?php

namespace Tests\Feature;

use App\Enums\AccuracyEvidenceStatus;
use App\Enums\AnswerKeyReviewStatus;
use App\Enums\ContentReviewStatus;
use App\Enums\ContentRightsReviewStatus;
use App\Enums\ExamAttemptStatus;
use App\Enums\ExamAttemptType;
use App\Enums\FigureReviewStatus;
use App\Enums\HumanAccuracyReviewDecision;
use App\Enums\QuestionStatus;
use App\Enums\StagedTranslationStatus;
use App\Exceptions\Exams\AnswerLockedException;
use App\Livewire\Admin\OriginalKarimenExamPreview;
use App\Models\ExamAttempt;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportEntryTranslation;
use App\Models\QuestionImportReviewAudit;
use App\Models\User;
use App\Models\UserQuestionMistake;
use App\Services\Editorial\KarimenReleaseReadinessService;
use App\Services\Editorial\OriginalKarimenExpansionStagingService;
use App\Services\Editorial\OriginalKarimenPilotAccuracyReviewService;
use App\Services\Editorial\OriginalKarimenPilotStagingService;
use App\Services\Editorial\OriginalKarimenPilotTranslationDraftService;
use App\Services\Exams\ExamAttemptService;
use App\Services\Exams\OriginalKarimenAdminPreviewService;
use App\Services\Exams\QuestionSelectionService;
use App\Services\Translations\StagedQuestionPublicationService;
use Database\Seeders\ExamTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OriginalKarimen50ExamActivationTest extends TestCase
{
    use RefreshDatabase;

    private function stageAllOriginal(): void
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

    #[Test]
    public function authorized_admin_can_start_private_preview_with_exactly_fifty_unique_original_candidates(): void
    {
        $this->stageAllOriginal();
        $admin = $this->admin();
        $preview = app(OriginalKarimenAdminPreviewService::class);

        $attempt = $preview->start($admin);

        $this->assertSame(ExamAttemptType::AdminPreview, $attempt->attempt_type);
        $this->assertFalse($attempt->counts_toward_analytics);
        $this->assertSame(50, $attempt->question_count);
        $this->assertSame(50, $attempt->questions()->count());
        $this->assertSame('ja', $attempt->locale);

        $candidateIds = $attempt->questions->map(
            fn ($question): string => (string) data_get($question->content_snapshot, 'admin_preview.candidate_id')
        )->sort()->values()->all();

        $this->assertCount(50, array_unique($candidateIds));
        $this->assertSame(50, count($candidateIds));
        $this->assertTrue(
            $attempt->questions->every(fn ($question): bool => $question->question_id === null)
        );
        $this->assertTrue(
            $attempt->questions->every(
                fn ($question): bool => (bool) data_get($question->content_snapshot, 'admin_preview.provisional_answers')
            )
        );
    }

    #[Test]
    public function clarification_candidates_are_flagged_in_preview_snapshots(): void
    {
        $this->stageAllOriginal();
        $attempt = app(OriginalKarimenAdminPreviewService::class)->start($this->admin());

        $flagged = $attempt->questions
            ->filter(fn ($question): bool => (bool) data_get($question->content_snapshot, 'admin_preview.needs_clarification'))
            ->map(fn ($question): string => (string) data_get($question->content_snapshot, 'admin_preview.candidate_id'))
            ->sort()
            ->values()
            ->all();

        $this->assertSame(5, count($flagged));
        $this->assertNotEmpty($flagged);
        foreach ([1, 8, 26, 34, 49] as $number) {
            $this->assertTrue(
                collect($flagged)->contains(fn (string $id): bool => str_ends_with($id, ':'.$number)),
                'Missing clarification flag for candidate '.$number
            );
        }
    }

    #[Test]
    public function guests_and_learners_cannot_enter_private_preview(): void
    {
        $this->stageAllOriginal();

        $this->get(route('admin.questions.original-karimen-exam-preview'))
            ->assertRedirect();

        $learner = User::factory()->create(['is_admin' => false]);
        $this->actingAs($learner)
            ->get(route('admin.questions.original-karimen-exam-preview'))
            ->assertForbidden();

        Livewire::actingAs($learner)
            ->test(OriginalKarimenExamPreview::class)
            ->assertForbidden();
    }

    #[Test]
    public function preview_question_text_does_not_leak_on_public_karimen_route(): void
    {
        $this->stageAllOriginal();
        $admin = $this->admin();
        $attempt = app(OriginalKarimenAdminPreviewService::class)->start($admin);
        $stem = (string) data_get($attempt->questions->first()->content_snapshot, 'japanese_text');

        $this->assertNotSame('', $stem);

        $this->get(route('exams.karimen'))
            ->assertOk()
            ->assertDontSee($stem, false);

        $learner = User::factory()->create(['is_admin' => false]);
        $this->actingAs($learner)
            ->get(route('exams.attempts.show', $attempt))
            ->assertNotFound();
    }

    #[Test]
    public function preview_scoring_locks_answers_and_does_not_affect_learner_stats_or_mistake_vault(): void
    {
        $this->stageAllOriginal();
        $admin = $this->admin();
        $preview = app(OriginalKarimenAdminPreviewService::class);
        $attempts = app(ExamAttemptService::class);

        $attempt = $preview->start($admin);
        $first = $attempt->questions->firstWhere('position', 1);
        $correct = (bool) data_get($first->content_snapshot, 'correct_answer');

        $result = $attempts->recordAnswer($attempt, $first->id, 0, $correct);
        $this->assertTrue($result['saved']);

        $this->expectException(AnswerLockedException::class);
        $attempts->recordAnswer($attempt->fresh(), $first->id, 0, ! $correct);
    }

    #[Test]
    public function preview_submission_is_isolated_from_dashboard_and_mistakes(): void
    {
        $this->stageAllOriginal();
        $admin = $this->admin();
        $preview = app(OriginalKarimenAdminPreviewService::class);
        $attempts = app(ExamAttemptService::class);

        $attempt = $preview->start($admin);

        foreach ($attempt->questions as $question) {
            $answer = (bool) data_get($question->content_snapshot, 'correct_answer');
            $attempts->recordAnswer($attempt->fresh()->loadForDisplay(), $question->id, 0, $answer);
        }

        $final = $attempts->submit($attempt->fresh());
        $this->assertSame(ExamAttemptStatus::Submitted, $final->status);
        $this->assertSame(50, $final->score);
        $this->assertFalse($final->counts_toward_analytics);
        $this->assertSame(0, UserQuestionMistake::query()->where('user_id', $admin->id)->count());

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('admin_preview', false);

        $history = ExamAttempt::query()
            ->whereBelongsTo($admin)
            ->where('attempt_type', '!=', ExamAttemptType::AdminPreview)
            ->count();
        $this->assertSame(0, $history);
    }

    #[Test]
    public function public_karimen_remains_gated_while_approvals_are_insufficient(): void
    {
        $this->stageAllOriginal();
        $karimen = ExamType::query()->where('slug', 'karimen')->firstOrFail();
        $availability = app(QuestionSelectionService::class)->fullAvailability($karimen, 'ja');

        $this->assertFalse($availability->canStart);
        $this->assertSame(0, $availability->availableStandard);

        $report = app(KarimenReleaseReadinessService::class)->report();
        $this->assertSame(50, $report['privately_staged_count']);
        $this->assertSame(0, $report['human_accuracy_accepted_count']);
        $this->assertSame(0, $report['published_count']);
        $this->assertFalse($report['japanese_public_karimen_can_start']);
        $this->assertFalse($report['public_release_ready']);
    }

    #[Test]
    public function release_check_command_is_read_only(): void
    {
        $this->stageAllOriginal();

        $before = QuestionImportEntry::query()
            ->orderBy('id')
            ->get(['id', 'updated_at', 'is_published', 'source_metadata', 'publication_blockers'])
            ->toJson();

        $this->artisan('menkyo:karimen-release-check')
            ->assertSuccessful()
            ->expectsOutputToContain('No records were modified');

        $after = QuestionImportEntry::query()
            ->orderBy('id')
            ->get(['id', 'updated_at', 'is_published', 'source_metadata', 'publication_blockers'])
            ->toJson();

        $this->assertSame($before, $after);
        $this->assertSame(0, QuestionImportReviewAudit::query()->where('action', 'like', '%release%')->count());
    }

    #[Test]
    public function eligible_fixture_can_publish_and_enter_public_pool_while_unapproved_cannot(): void
    {
        $this->seed(ExamTypeSeeder::class);
        $admin = $this->admin();
        $publication = app(StagedQuestionPublicationService::class);

        $blocked = QuestionImportEntry::factory()->create([
            'exam_family' => 'karimen',
            'answer_key_status' => AnswerKeyReviewStatus::VerificationPending,
            'content_rights_status' => ContentRightsReviewStatus::Unclear,
            'accuracy_evidence_status' => AccuracyEvidenceStatus::NotChecked,
            'review_flags' => [],
            'editorial_issues' => [],
        ]);
        QuestionImportEntryTranslation::factory()->create([
            'question_import_entry_id' => $blocked->id,
            'locale' => 'ja',
            'status' => StagedTranslationStatus::Draft,
        ]);
        QuestionImportEntryTranslation::factory()->create([
            'question_import_entry_id' => $blocked->id,
            'locale' => 'en',
            'question_text' => $blocked->english_question,
            'explanation' => $blocked->english_explanation,
        ]);

        $blockedResult = $publication->publishToLivePool($blocked->fresh(['translations']), $admin);
        $this->assertFalse($blockedResult['published']);
        $this->assertSame(0, Question::query()->where('status', QuestionStatus::Published)->count());

        $ready = QuestionImportEntry::factory()->create([
            'exam_family' => 'karimen',
            'source_group' => 'fixture_publish_test',
            'answer_key_status' => AnswerKeyReviewStatus::Verified,
            'answer_key_reference' => 'Road Traffic Act Art. fixture',
            'answer_key_verified_at' => now(),
            'content_rights_status' => ContentRightsReviewStatus::ApprovedForPublicReuse,
            'accuracy_evidence_status' => AccuracyEvidenceStatus::HumanReviewed,
            'accuracy_verification_notes' => 'Human-reviewed fixture for publication path.',
            'figure_review_status' => FigureReviewStatus::NotApplicable,
            'review_flags' => [],
            'editorial_issues' => [],
            'correct_answer' => true,
        ]);
        QuestionImportEntryTranslation::factory()->create([
            'question_import_entry_id' => $ready->id,
            'locale' => 'ja',
            'question_text' => '公開適格フィクスチャの仮免問題です。',
            'explanation' => 'フィクスチャ解説',
            'status' => StagedTranslationStatus::ReadyForPublication,
            'reviewed_at' => now(),
        ]);
        QuestionImportEntryTranslation::factory()->create([
            'question_import_entry_id' => $ready->id,
            'locale' => 'en',
            'question_text' => $ready->english_question,
            'explanation' => $ready->english_explanation,
            'status' => StagedTranslationStatus::Draft,
        ]);

        $published = $publication->publishToLivePool($ready->fresh(['translations', 'batch']), $admin);
        $this->assertTrue($published['published']);
        $question = Question::query()->findOrFail($published['question_id']);
        $this->assertSame(QuestionStatus::Published, $question->status);
        $this->assertSame(ContentReviewStatus::Reviewed, $question->content_review_status);
        $this->assertTrue($ready->fresh()->is_published);
        $this->assertSame(
            1,
            QuestionImportReviewAudit::query()
                ->where('question_import_entry_id', $ready->id)
                ->where('action', StagedQuestionPublicationService::AUDIT_ACTION)
                ->count()
        );

        $karimen = ExamType::query()->where('slug', 'karimen')->firstOrFail();
        $this->assertTrue($question->examTypes->contains(fn (ExamType $type): bool => $type->is($karimen))
            || $question->examTypes()->whereKey($karimen->id)->exists());
    }

    #[Test]
    public function stale_human_accuracy_is_rejected_and_no_fabricated_review_events_are_created(): void
    {
        $this->stageAllOriginal();
        $entry = QuestionImportEntry::query()
            ->where('source_group', OriginalKarimenPilotStagingService::SOURCE_GROUP)
            ->where('original_question_number', 2)
            ->firstOrFail();

        $reviews = app(OriginalKarimenPilotAccuracyReviewService::class);
        $fingerprint = $reviews->reviewContentFingerprint($entry);
        $entry->forceFill([
            'source_metadata' => array_merge($entry->source_metadata ?? [], [
                'human_accuracy_review' => true,
                'human_accuracy_decision' => HumanAccuracyReviewDecision::Accepted->value,
                'human_accuracy_reviewed_content_fingerprint' => '0'.substr($fingerprint, 1),
            ]),
            'accuracy_evidence_status' => AccuracyEvidenceStatus::HumanReviewed,
        ])->save();

        $auditBefore = QuestionImportReviewAudit::query()->count();
        $this->assertFalse($reviews->humanAccuracyAcceptedForCurrentVersion($entry->fresh()));
        $this->assertSame($auditBefore, QuestionImportReviewAudit::query()->count());
    }

    #[Test]
    public function translation_eligibility_is_enforced_per_locale_and_imported_counts_stay_stable_in_report(): void
    {
        $this->stageAllOriginal();
        $karimen = ExamType::query()->where('slug', 'karimen')->firstOrFail();
        $selection = app(QuestionSelectionService::class);

        foreach (['en', 'zh', 'vi', 'fil', 'ne', 'bn', 'ko'] as $locale) {
            $availability = $selection->fullAvailability($karimen, $locale);
            $this->assertFalse($availability->canStart, $locale.' should remain unavailable');
        }

        $imported = QuestionImportEntry::query()
            ->whereNotIn('source_group', [
                OriginalKarimenPilotStagingService::SOURCE_GROUP,
                OriginalKarimenExpansionStagingService::SOURCE_GROUP,
            ])
            ->count();
        $this->assertSame(0, $imported);

        $drafts = QuestionImportEntryTranslation::query()
            ->whereHas('entry', fn ($q) => $q->where('source_group', OriginalKarimenPilotStagingService::SOURCE_GROUP))
            ->where('locale', '!=', 'ja')
            ->where(
                'japanese_reading_data->pilot_translation_meta->drafting_method',
                OriginalKarimenPilotTranslationDraftService::DRAFTING_METHOD,
            )
            ->count();
        $this->assertSame(56, $drafts);
    }

    #[Test]
    public function public_start_request_rejects_admin_preview_attempt_type(): void
    {
        $this->seed(ExamTypeSeeder::class);
        $user = User::factory()->create();
        $karimen = ExamType::query()->where('slug', 'karimen')->firstOrFail();

        $this->actingAs($user)
            ->post(route('exams.attempts.store', $karimen), [
                'attempt_type' => ExamAttemptType::AdminPreview->value,
            ])
            ->assertSessionHasErrors('attempt_type');
    }

    #[Test]
    public function adsense_remains_disabled_by_default(): void
    {
        $this->assertFalse(filter_var(env('ADSENSE_ENABLED', false), FILTER_VALIDATE_BOOLEAN));
        $this->assertFalse((bool) config('ads.enabled'));
    }
}
