<?php

namespace Tests\Feature\Exams;

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamAttemptType;
use App\Exceptions\Exams\AnswerLockedException;
use App\Exceptions\Exams\InvalidExamProgressException;
use App\Livewire\ExamRunner;
use App\Models\ExamAttemptAnswer;
use App\Services\Exams\ExamAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OneWayExamFlowTest extends TestCase
{
    use CreatesPracticeExams;
    use RefreshDatabase;

    public function test_selecting_an_answer_saves_once_and_auto_advances(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(2, 0, 2);
        $this->publishedTrueFalse($examType, true, ['japanese_text' => 'Q1']);
        $this->publishedTrueFalse($examType, false, ['japanese_text' => 'Q2']);
        $service = app(ExamAttemptService::class);
        $attempt = $service->start($user, $examType, ExamAttemptType::Full);
        $firstId = $attempt->questions->firstWhere('position', 1)->id;

        $result = $service->recordAnswer($attempt, $firstId, 0, true);

        $this->assertTrue($result['saved']);
        $this->assertTrue($result['advanced']);
        $this->assertSame(2, $result['position']);
        $this->assertSame(1, ExamAttemptAnswer::query()->count());
        $this->assertSame(2, $attempt->fresh()->current_position);
    }

    public function test_locked_answers_cannot_be_changed_or_replayed_from_another_tab(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(2, 0, 2);
        $this->publishedTrueFalse($examType, true);
        $this->publishedTrueFalse($examType, true);
        $service = app(ExamAttemptService::class);
        $attempt = $service->start($user, $examType, ExamAttemptType::Full);
        $firstId = $attempt->questions->firstWhere('position', 1)->id;

        $service->recordAnswer($attempt, $firstId, 0, true);

        $this->expectException(AnswerLockedException::class);
        $service->recordAnswer($attempt->fresh(), $firstId, 0, false);
    }

    public function test_server_rejects_answers_for_future_questions(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(2, 0, 2);
        $this->publishedTrueFalse($examType, true);
        $this->publishedTrueFalse($examType, true);
        $service = app(ExamAttemptService::class);
        $attempt = $service->start($user, $examType, ExamAttemptType::Full);
        $secondId = $attempt->questions->firstWhere('position', 2)->id;

        $this->expectException(InvalidExamProgressException::class);
        $service->recordAnswer($attempt, $secondId, 0, true);
    }

    public function test_free_navigation_is_disabled(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(2, 0, 2);
        $this->publishedTrueFalse($examType, true);
        $this->publishedTrueFalse($examType, true);
        $service = app(ExamAttemptService::class);
        $attempt = $service->start($user, $examType, ExamAttemptType::Full);

        $this->expectException(InvalidExamProgressException::class);
        $service->move($attempt, 2);
    }

    public function test_refresh_keeps_progress_at_earliest_unanswered_position(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(3, 0, 3);
        $this->publishedTrueFalse($examType, true);
        $this->publishedTrueFalse($examType, true);
        $this->publishedTrueFalse($examType, true);
        $service = app(ExamAttemptService::class);
        $attempt = $service->start($user, $examType, ExamAttemptType::Full);
        $firstId = $attempt->questions->firstWhere('position', 1)->id;
        $service->recordAnswer($attempt, $firstId, 0, true);

        $attempt->forceFill(['current_position' => 1])->save();
        $synced = $service->syncProgress($attempt->fresh());

        $this->assertSame(2, $synced->current_position);
    }

    public function test_double_click_does_not_create_two_answers_or_skip_ahead(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(3, 0, 3);
        $this->publishedTrueFalse($examType, true);
        $this->publishedTrueFalse($examType, true);
        $this->publishedTrueFalse($examType, true);
        $attempt = app(ExamAttemptService::class)->start($user, $examType, ExamAttemptType::Full);
        $firstId = $attempt->questions->firstWhere('position', 1)->id;

        Livewire::actingAs($user)
            ->test(ExamRunner::class, ['attempt' => $attempt])
            ->call('selectAnswer', $firstId, 0, true)
            ->call('selectAnswer', $firstId, 0, false)
            ->assertSet('answerError', __('exams.error_answer_locked'));

        $this->assertSame(1, ExamAttemptAnswer::query()->count());
        $this->assertSame(2, $attempt->fresh()->current_position);
        $this->assertTrue((bool) ExamAttemptAnswer::query()->first()->selected_answer);

        // Concurrent second-tab style replay must also fail against the locked slot.
        try {
            app(ExamAttemptService::class)->recordAnswer($attempt->fresh(), $firstId, 0, false);
            $this->fail('Locked answer was overwritten.');
        } catch (AnswerLockedException) {
            $this->assertSame(1, ExamAttemptAnswer::query()->count());
        }
    }

    public function test_illustrated_group_requires_confirm_before_advance(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(0, 1, 2);
        $this->publishedIllustrated($examType, [true, false, true]);
        $service = app(ExamAttemptService::class);
        $attempt = $service->start($user, $examType, ExamAttemptType::Full);
        $group = $attempt->questions->first();

        $service->recordAnswer($attempt, $group->id, 1, true);
        $service->recordAnswer($attempt->fresh(), $group->id, 2, false);
        $afterThree = $service->recordAnswer($attempt->fresh(), $group->id, 3, true);

        $this->assertTrue($afterThree['unit_complete']);
        $this->assertFalse($afterThree['advanced']);
        $this->assertSame(1, $attempt->fresh()->current_position);

        $confirmed = $service->confirmIllustratedGroup($attempt->fresh(), $group->id);

        $this->assertFalse($confirmed['advanced']);
        $this->assertTrue($confirmed['ready_to_submit']);
        $this->assertSame(1, $confirmed['position']);
    }

    public function test_final_question_opens_submit_modal_and_cancel_keeps_locks(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(1, 0, 1);
        $this->publishedTrueFalse($examType, true);
        $attempt = app(ExamAttemptService::class)->start($user, $examType, ExamAttemptType::Full);
        $questionId = $attempt->questions->first()->id;

        Livewire::actingAs($user)
            ->test(ExamRunner::class, ['attempt' => $attempt])
            ->assertSee('data-exam-timer-card', false)
            ->assertDontSee('googlesyndication', false)
            ->call('selectAnswer', $questionId, 0, true)
            ->assertSet('showSubmitModal', true)
            ->call('closeSubmitModal')
            ->assertSet('showSubmitModal', false);

        $this->expectException(AnswerLockedException::class);
        app(ExamAttemptService::class)->recordAnswer($attempt->fresh(), $questionId, 0, false);
    }

    public function test_duplicate_submit_is_idempotent_and_results_list_incorrect_answers(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(2, 0, 2);
        $this->publishedTrueFalse($examType, true, ['japanese_text' => 'Correct stem']);
        $this->publishedTrueFalse($examType, true, ['japanese_text' => 'Wrong stem']);
        $service = app(ExamAttemptService::class);
        $attempt = $service->start($user, $examType, ExamAttemptType::Full);

        $first = $attempt->questions->firstWhere('position', 1);
        $service->recordAnswer($attempt, $first->id, 0, (bool) $first->content_snapshot['correct_answer']);
        $second = $attempt->fresh()->loadForDisplay()->questions->firstWhere('position', 2);
        $service->recordAnswer($attempt->fresh(), $second->id, 0, ! (bool) $second->content_snapshot['correct_answer']);

        $final = $service->submit($attempt->fresh());
        $again = $service->submit($final->fresh());

        $this->assertSame($final->score, $again->score);
        $this->assertSame(ExamAttemptStatus::Submitted, $again->status);

        $wrongStem = $second->content_snapshot['japanese_text'];

        $this->actingAs($user)
            ->get(route('exams.attempts.results', $again))
            ->assertSuccessful()
            ->assertSee('data-exam-results', false)
            ->assertSee('data-incorrect-review', false)
            ->assertSee($wrongStem, false)
            ->assertSee(__('results.your_answer', [], 'ja'), false)
            ->assertSee(__('results.correct_answer', [], 'ja'), false);
    }

    public function test_eight_locales_include_submit_modal_copy(): void
    {
        foreach (['ja', 'en', 'zh', 'vi', 'fil', 'ne', 'bn', 'ko'] as $locale) {
            $this->assertNotSame('', __('exams.submit_modal_title', [], $locale));
            $this->assertNotSame('exams.submit_modal_title', __('exams.submit_modal_title', [], $locale));
            $this->assertNotSame('', __('results.incorrect_answers_heading', [], $locale));
        }
    }
}
