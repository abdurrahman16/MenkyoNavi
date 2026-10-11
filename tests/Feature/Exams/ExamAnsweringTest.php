<?php

namespace Tests\Feature\Exams;

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamAttemptType;
use App\Exceptions\Exams\AnswerLockedException;
use App\Exceptions\Exams\ExamClosedException;
use App\Livewire\ExamRunner;
use App\Models\ExamAttemptAnswer;
use App\Models\User;
use App\Services\Exams\ExamAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExamAnsweringTest extends TestCase
{
    use CreatesPracticeExams;
    use RefreshDatabase;

    public function test_user_can_save_an_answer_once_without_seeing_the_explanation(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(1, 0, 1);
        $this->publishedTrueFalse($examType, true, [
            'explanation_ja' => 'HIDDEN_EXPLANATION_MARKER',
            'japanese_reading_data' => [
                ['text' => '<script>alert(1)</script>', 'reading' => 'テスト'],
            ],
        ]);
        $attempt = app(ExamAttemptService::class)->start($user, $examType, ExamAttemptType::Full);
        $attemptQuestion = $attempt->questions->first();

        Livewire::actingAs($user)
            ->test(ExamRunner::class, ['attempt' => $attempt])
            ->call('selectAnswer', $attemptQuestion->id, 0, true)
            ->call('selectAnswer', $attemptQuestion->id, 0, false)
            ->assertSet('answerError', __('exams.error_answer_locked'));

        $this->assertSame(1, ExamAttemptAnswer::query()->count());
        $answer = ExamAttemptAnswer::query()->first();
        $this->assertTrue($answer->selected_answer);
        $this->assertTrue($answer->is_correct);
        $this->assertSame(1, $answer->points_awarded);

        Livewire::actingAs($user)
            ->test(ExamRunner::class, ['attempt' => $attempt->fresh()])
            ->assertSee('data-one-way="true"', false)
            ->assertSee('data-exam-complete-card', false)
            ->assertDontSee('HIDDEN_EXPLANATION_MARKER', false)
            ->assertDontSee('data-ad-slot', false);

        $this->actingAs($user)
            ->get(route('exams.attempts.show', $attempt))
            ->assertSuccessful()
            ->assertSee('data-one-way="true"', false)
            ->assertDontSee('HIDDEN_EXPLANATION_MARKER', false);
    }

    public function test_timer_deadline_stays_fixed_when_the_page_is_reloaded(): void
    {
        $this->freezeTime();
        $user = $this->learner();
        $examType = $this->examTypeWithRules(1, 0, 1, 30);
        $this->publishedTrueFalse($examType, true);
        $attempt = app(ExamAttemptService::class)->start($user, $examType, ExamAttemptType::Full);
        $deadline = $attempt->expires_at->toIso8601String();

        $this->travel(10)->minutes();

        $this->actingAs($user)
            ->get(route('exams.attempts.show', $attempt))
            ->assertSuccessful()
            ->assertSee('data-exam-timer-card', false);

        $attempt->refresh();
        $this->assertSame($deadline, $attempt->expires_at->toIso8601String());
        $this->assertSame(ExamAttemptStatus::InProgress, $attempt->status);
        $this->assertSame(now()->addMinutes(20)->getTimestamp(), $attempt->expires_at->getTimestamp());
    }

    public function test_expired_attempt_is_timed_out_and_rejects_later_answers(): void
    {
        $this->freezeTime();
        $user = $this->learner();
        $examType = $this->examTypeWithRules(1, 0, 1);
        $this->publishedTrueFalse($examType, true);
        $attempt = app(ExamAttemptService::class)->start($user, $examType, ExamAttemptType::Full);
        $attemptQuestionId = $attempt->questions->first()->id;

        $this->travelTo($attempt->expires_at->copy()->addSecond());

        $this->actingAs($user)
            ->get(route('exams.attempts.show', $attempt))
            ->assertRedirect(route('exams.attempts.results', $attempt));

        $attempt->refresh();
        $this->assertSame(ExamAttemptStatus::TimedOut, $attempt->status);
        $this->assertSame(0, $attempt->score);

        try {
            app(ExamAttemptService::class)->recordAnswer($attempt, $attemptQuestionId, 0, true);
            $this->fail('An expired examination accepted an answer.');
        } catch (ExamClosedException) {
            $this->assertSame(0, ExamAttemptAnswer::query()->count());
        }
    }

    public function test_submitting_twice_keeps_the_finalized_score(): void
    {
        $this->freezeTime();
        $user = $this->learner();
        $examType = $this->examTypeWithRules(1, 0, 1);
        $this->publishedTrueFalse($examType, true);
        $service = app(ExamAttemptService::class);
        $attempt = $service->start($user, $examType, ExamAttemptType::Full);
        $service->recordAnswer($attempt, $attempt->questions->first()->id, 0, true);
        $this->travel(2)->minutes();

        $final = $service->submit($attempt);
        $submittedAt = $final->submitted_at->toIso8601String();

        ExamAttemptAnswer::query()->first()->update([
            'selected_answer' => false,
            'is_correct' => false,
            'points_awarded' => 0,
        ]);

        $again = $service->submit($final->fresh());

        $this->assertSame(1, $again->score);
        $this->assertTrue($again->passed);
        $this->assertSame(120, $again->duration_seconds);
        $this->assertSame($submittedAt, $again->submitted_at->toIso8601String());
        $this->assertSame(ExamAttemptStatus::Submitted, $again->status);
    }

    public function test_another_user_cannot_open_a_private_attempt(): void
    {
        $owner = $this->learner();
        $other = User::factory()->create();
        $examType = $this->examTypeWithRules(1, 0, 1);
        $this->publishedTrueFalse($examType, true);
        $attempt = app(ExamAttemptService::class)->start($owner, $examType, ExamAttemptType::Full);

        $this->assertTrue($owner->can('view', $attempt));
        $this->assertTrue($owner->can('update', $attempt));
        $this->assertFalse($other->can('view', $attempt));
        $this->assertFalse($other->can('update', $attempt));

        $attempt->forceFill(['status' => ExamAttemptStatus::Submitted])->save();
        $this->assertFalse($owner->can('update', $attempt->fresh()));
        $this->assertTrue($owner->can('review', $attempt->fresh()));
        $this->assertFalse($other->can('review', $attempt->fresh()));

        $this->actingAs($other)
            ->get(route('exams.attempts.show', $attempt))
            ->assertNotFound();

        $this->actingAs($other)
            ->get(route('exams.attempts.results', $attempt))
            ->assertNotFound();

        $this->actingAs($other)
            ->get(route('exams.attempts.review', $attempt))
            ->assertNotFound();
    }

    public function test_locked_answer_exception_is_raised_for_direct_service_replay(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(1, 0, 1);
        $this->publishedTrueFalse($examType, true);
        $service = app(ExamAttemptService::class);
        $attempt = $service->start($user, $examType, ExamAttemptType::Full);
        $questionId = $attempt->questions->first()->id;
        $service->recordAnswer($attempt, $questionId, 0, true);

        $this->expectException(AnswerLockedException::class);
        $service->recordAnswer($attempt->fresh(), $questionId, 0, false);
    }
}
