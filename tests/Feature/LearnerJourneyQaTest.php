<?php

namespace Tests\Feature;

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamAttemptType;
use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Livewire\ExamRunner;
use App\Models\ExamAttempt;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\User;
use App\Models\UserQuestionMistake;
use App\Services\Exams\ExamAttemptService;
use Database\Seeders\DemoQuestionSeeder;
use Database\Seeders\ExamTypeSeeder;
use Database\Seeders\QuestionCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Exams\CreatesPracticeExams;
use Tests\TestCase;

class LearnerJourneyQaTest extends TestCase
{
    use CreatesPracticeExams;
    use RefreshDatabase;

    public function test_public_learning_path_links_stay_available_for_guests(): void
    {
        $this->withSession(['locale' => 'ja'])
            ->get(route('home'))
            ->assertSuccessful()
            ->assertSee(route('learn.index', absolute: false), false);

        $this->withSession(['locale' => 'ja'])
            ->get(route('learn.index'))
            ->assertSuccessful()
            ->assertSee('data-study-checklist', false)
            ->assertSee('data-study-checklist-local-note', false)
            ->assertSee(__('pages.learn.checklist_note', [], 'ja'), false)
            ->assertSee(route('learn.road-signs', absolute: false), false)
            ->assertSee(route('learn.study-methods', absolute: false), false)
            ->assertSee(route('exams.index', absolute: false), false);

        foreach ([
            'learn.road-signs',
            'learn.study-methods',
            'learn.traffic-rules.pedestrian-priority',
            'learn.traffic-rules.intersection-priority',
            'exams.index',
            'exams.karimen',
            'exams.honmen',
            'exams.gaimen-kirikae',
            'contact',
            'robots',
            'sitemap',
        ] as $routeName) {
            $this->get(route($routeName))->assertSuccessful();
        }
    }

    public function test_unverified_users_are_redirected_from_protected_learner_routes(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($user)
            ->get(route('exams.attempts.index'))
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($user)
            ->get(route('mistakes.index'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_guests_must_log_in_before_starting_practice(): void
    {
        $this->seed([ExamTypeSeeder::class, QuestionCategorySeeder::class, DemoQuestionSeeder::class]);

        $examType = ExamType::query()->where('slug', 'karimen')->firstOrFail();

        $this->post(route('exams.attempts.store', $examType), [
            'attempt_type' => ExamAttemptType::Demo->value,
        ])->assertRedirect(route('login'));
    }

    public function test_end_to_end_demo_journey_through_livewire_submit_and_learning_return(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(2, 0, 2);
        $this->attachDemoTrueFalse($examType, true, 'Journey Q1 stem');
        $this->attachDemoTrueFalse($examType, false, 'Journey Q2 stem');

        $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('learn.index'))
            ->assertSuccessful();

        $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->post(route('exams.attempts.store', $examType), [
                'attempt_type' => ExamAttemptType::Demo->value,
            ])
            ->assertRedirect();

        $attempt = ExamAttempt::query()->where('user_id', $user->id)->latest('id')->firstOrFail();
        $this->assertSame(ExamAttemptType::Demo, $attempt->attempt_type);
        $this->assertSame(ExamAttemptStatus::InProgress, $attempt->status);

        $firstId = $attempt->questions->firstWhere('position', 1)->id;
        $secondId = $attempt->questions->firstWhere('position', 2)->id;

        Livewire::actingAs($user)
            ->test(ExamRunner::class, ['attempt' => $attempt])
            ->assertDontSee('adsbygoogle', false)
            ->assertDontSee('data-ad-slot', false)
            ->call('selectAnswer', $firstId, 0, true)
            ->call('selectAnswer', $secondId, 0, true)
            ->assertSet('showSubmitModal', true)
            ->call('closeSubmitModal')
            ->assertSet('showSubmitModal', false)
            ->call('openSubmitModal')
            ->call('submitExam')
            ->assertRedirect(route('exams.attempts.results', $attempt));

        $final = $attempt->fresh();
        $this->assertSame(ExamAttemptStatus::Submitted, $final->status);
        $this->assertSame(0, UserQuestionMistake::query()->where('user_id', $user->id)->count());

        $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('exams.attempts.results', $final))
            ->assertSuccessful()
            ->assertSee('data-exam-results', false)
            ->assertSee(route('learn.index', absolute: false), false)
            ->assertSee(route('learn.road-signs', absolute: false), false)
            ->assertSee(route('exams.attempts.review', $final, absolute: false), false);

        $this->actingAs($user)
            ->get(route('exams.attempts.review', $final))
            ->assertSuccessful()
            ->assertSee('Journey Q1 stem', false)
            ->assertSee('Journey Q2 stem', false);

        $this->actingAs($user)
            ->get(route('learn.road-signs'))
            ->assertSuccessful()
            ->assertSee('data-sign-details-card', false)
            ->assertSee('data-sign-recognition-practice', false);
    }

    public function test_livewire_expires_attempt_on_mount_with_localized_notice(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(1, 0, 1, minutes: 1);
        $this->publishedTrueFalse($examType, true);
        $attempt = app(ExamAttemptService::class)->start($user, $examType, ExamAttemptType::Full);
        $attempt->forceFill(['expires_at' => now()->subSecond()])->save();

        app()->setLocale('ja');

        Livewire::actingAs($user)
            ->test(ExamRunner::class, ['attempt' => $attempt->fresh()])
            ->assertRedirect(route('exams.attempts.results', $attempt));

        $this->assertSame(ExamAttemptStatus::TimedOut, $attempt->fresh()->status);
        $this->assertSame(__('exams.time_expired', [], 'ja'), session('exam_notice'));
    }

    public function test_livewire_close_if_expired_poll_finalizes_in_progress_attempt(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(1, 0, 1, minutes: 5);
        $this->publishedTrueFalse($examType, true);
        $attempt = app(ExamAttemptService::class)->start($user, $examType, ExamAttemptType::Full);

        app()->setLocale('en');

        $component = Livewire::actingAs($user)
            ->test(ExamRunner::class, ['attempt' => $attempt]);

        $attempt->forceFill(['expires_at' => now()->subSecond()])->save();

        $component
            ->call('closeIfExpired')
            ->assertRedirect(route('exams.attempts.results', $attempt));

        $this->assertSame(ExamAttemptStatus::TimedOut, $attempt->fresh()->status);
        $this->assertSame(__('exams.time_expired', [], 'en'), session('exam_notice'));
    }

    public function test_http_submit_uses_localized_timeout_notice(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(1, 0, 1, minutes: 1);
        $this->publishedTrueFalse($examType, true);
        $attempt = app(ExamAttemptService::class)->start($user, $examType, ExamAttemptType::Full);
        $attempt->forceFill(['expires_at' => now()->subMinutes(2)])->save();

        $this->actingAs($user)
            ->withSession(['locale' => 'ja'])
            ->post(route('exams.attempts.submit', $attempt))
            ->assertRedirect(route('exams.attempts.results', $attempt))
            ->assertSessionHas('exam_notice', __('exams.time_expired', [], 'ja'));

        $this->assertNotSame(
            'Time expired. Your examination was submitted automatically.',
            session('exam_notice'),
        );
    }

    public function test_other_users_cannot_open_results_or_submit_private_attempts(): void
    {
        $owner = $this->learner();
        $intruder = $this->learner();
        $examType = $this->examTypeWithRules(1, 0, 1);
        $this->publishedTrueFalse($examType, true);
        $attempt = app(ExamAttemptService::class)->start($owner, $examType, ExamAttemptType::Full);

        // Ownership is enforced in route binding (404) so attempt UUIDs are not enumerable across accounts.
        $this->actingAs($intruder)
            ->get(route('exams.attempts.show', $attempt))
            ->assertNotFound();

        $this->actingAs($intruder)
            ->get(route('exams.attempts.results', $attempt))
            ->assertNotFound();

        $this->actingAs($intruder)
            ->post(route('exams.attempts.submit', $attempt))
            ->assertNotFound();
    }

    public function test_stable_shuffle_survives_resume_after_partial_answers(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(3, 0, 3);
        $this->publishedTrueFalse($examType, true, ['japanese_text' => 'Stem A']);
        $this->publishedTrueFalse($examType, true, ['japanese_text' => 'Stem B']);
        $this->publishedTrueFalse($examType, true, ['japanese_text' => 'Stem C']);
        $service = app(ExamAttemptService::class);
        $attempt = $service->start($user, $examType, ExamAttemptType::Full);

        $orderBefore = $attempt->questions->sortBy('position')->pluck('question_id')->values()->all();
        $first = $attempt->questions->firstWhere('position', 1);
        $service->recordAnswer($attempt, $first->id, 0, true);

        $resumed = $service->syncProgress($attempt->fresh());
        $orderAfter = $resumed->questions()->orderBy('position')->pluck('question_id')->values()->all();

        $this->assertSame($orderBefore, $orderAfter);
        $this->assertSame(2, $resumed->current_position);

        $this->actingAs($user)
            ->get(route('exams.attempts.show', $resumed))
            ->assertSuccessful()
            ->assertSee('data-exam-runner', false);
    }

    public function test_study_methods_plans_disclose_browser_local_checklist_storage(): void
    {
        $this->withSession(['locale' => 'en'])
            ->get(route('learn.study-methods'))
            ->assertSuccessful()
            ->assertSee('data-study-plans', false)
            ->assertSee(__('study_methods.plans.local_only_note', [], 'en'), false)
            ->assertDontSee('server-synced account progress', false);
    }

    public function test_adsense_remains_disabled_on_exam_results_and_learning_surfaces(): void
    {
        config([
            'ads.enabled' => false,
            'ads.publisher_id' => null,
            'ads.layout_preview' => false,
        ]);

        $user = $this->learner();
        $examType = $this->examTypeWithRules(1, 0, 1);
        $this->publishedTrueFalse($examType, true);
        $service = app(ExamAttemptService::class);
        $attempt = $service->start($user, $examType, ExamAttemptType::Full);

        $this->actingAs($user)
            ->get(route('exams.attempts.show', $attempt))
            ->assertSuccessful()
            ->assertDontSee('adsbygoogle', false)
            ->assertDontSee('googlesyndication', false)
            ->assertDontSee('data-ad-slot', false);

        $question = $attempt->questions->first();
        $service->recordAnswer($attempt, $question->id, 0, true);
        $final = $service->submit($attempt->fresh());

        foreach ([
            route('exams.attempts.results', $final),
            route('exams.attempts.review', $final),
            route('learn.road-signs'),
            route('contact'),
        ] as $url) {
            $this->actingAs($user)
                ->get($url)
                ->assertSuccessful()
                ->assertDontSee('adsbygoogle', false)
                ->assertDontSee('data-ad-slot', false);
        }
    }

    public function test_eight_locales_expose_exam_timeout_and_checklist_copy(): void
    {
        foreach (['ja', 'en', 'zh', 'vi', 'fil', 'ne', 'bn', 'ko'] as $locale) {
            foreach ([
                'exams.time_expired',
                'exams.submit_modal_title',
                'exams.confirm_submit',
                'pages.learn.checklist_note',
                'study_methods.plans.local_only_note',
                'results.incorrect_answers_heading',
            ] as $key) {
                $value = __($key, [], $locale);
                $this->assertNotSame($key, $value, "Missing {$key} for {$locale}");
                $this->assertNotSame('', $value);
            }
        }
    }

    private function attachDemoTrueFalse(ExamType $examType, bool $answer, string $stem): Question
    {
        $question = Question::factory()->demo()->create([
            'status' => QuestionStatus::Demo,
            'is_demo' => true,
            'type' => QuestionType::StandardTrueFalse,
            'correct_answer' => $answer,
            'japanese_text' => $stem,
            'points' => 1,
        ]);
        $question->examTypes()->attach($examType->id);

        return $question;
    }
}
