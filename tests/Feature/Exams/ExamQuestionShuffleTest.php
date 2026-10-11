<?php

namespace Tests\Feature\Exams;

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamAttemptType;
use App\Livewire\ExamRunner;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Services\Exams\ExamAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExamQuestionShuffleTest extends TestCase
{
    use CreatesPracticeExams;
    use RefreshDatabase;

    public function test_new_attempts_persist_a_shuffled_order_that_stays_stable_on_refresh(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(5, 0, 5, 30);

        $questions = collect(range(1, 8))->map(
            fn (int $index): Question => $this->publishedTrueFalse($examType, $index % 2 === 0, [
                'japanese_text' => 'Shuffle marker '.$index,
            ])
        );

        $service = app(ExamAttemptService::class);
        $attempt = $service->start($user, $examType, ExamAttemptType::Full);
        $order = $attempt->questions()->orderBy('position')->pluck('question_id')->all();

        $this->assertCount(5, $order);
        $this->assertSame(range(1, 5), $attempt->questions()->orderBy('position')->pluck('position')->all());

        $this->actingAs($user)
            ->get(route('exams.attempts.show', $attempt))
            ->assertSuccessful()
            ->assertSee('data-exam-runner', false)
            ->assertSee('data-exam-palette', false)
            ->assertDontSee('googlesyndication', false)
            ->assertDontSee('data-ad-placement', false);

        $reloaded = $service->start($user, $examType, ExamAttemptType::Full);
        $this->assertSame($attempt->id, $reloaded->id);
        $this->assertSame(
            $order,
            $reloaded->questions()->orderBy('position')->pluck('question_id')->all()
        );

        $first = $attempt->questions()->where('position', 1)->firstOrFail();
        Livewire::actingAs($user)
            ->test(ExamRunner::class, ['attempt' => $attempt])
            ->call('selectAnswer', $first->id, 0, true);

        $afterNav = ExamAttempt::query()->with('questions.answers')->findOrFail($attempt->id);
        $this->assertSame(2, $afterNav->current_position);
        $this->assertSame($order, $afterNav->questions->sortBy('position')->pluck('question_id')->values()->all());
        $this->assertTrue($afterNav->questions->firstWhere('position', 1)->answers->isNotEmpty());
        $this->assertSame($first->id, $afterNav->questions->firstWhere('position', 1)->id);
        $this->assertContains($first->question_id, $questions->pluck('id')->all());
    }

    public function test_different_attempts_can_receive_different_question_orders(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(5, 0, 5, 30);

        foreach (range(1, 10) as $index) {
            $this->publishedTrueFalse($examType, true, [
                'japanese_text' => 'Order pool '.$index,
            ]);
        }

        $service = app(ExamAttemptService::class);
        $orders = [];

        for ($i = 0; $i < 12; $i++) {
            $attempt = $service->start($user, $examType, ExamAttemptType::Full);
            $orders[] = $attempt->questions()->orderBy('position')->pluck('question_id')->implode('-');
            $service->submit($attempt);
            $this->assertSame(ExamAttemptStatus::Submitted, $attempt->fresh()->status);
        }

        $this->assertGreaterThan(
            1,
            count(array_unique($orders)),
            'Expected at least two distinct attempt orderings from a non-trivial pool.'
        );
    }

    public function test_results_and_review_use_the_same_stored_attempt_order(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(3, 0, 2, 30);
        $this->publishedTrueFalse($examType, true, ['japanese_text' => 'Alpha stem']);
        $this->publishedTrueFalse($examType, false, ['japanese_text' => 'Bravo stem']);
        $this->publishedTrueFalse($examType, true, ['japanese_text' => 'Charlie stem']);

        $service = app(ExamAttemptService::class);
        $attempt = $service->start($user, $examType, ExamAttemptType::Full);
        $order = $attempt->questions()->orderBy('position')->pluck('question_id')->all();

        foreach ($attempt->questions as $question) {
            $service->recordAnswer($attempt, $question->id, 0, true);
        }

        $submitted = $service->submit($attempt->fresh()->loadForDisplay());
        $this->assertSame($order, $submitted->questions()->orderBy('position')->pluck('question_id')->all());

        $review = $this->actingAs($user)
            ->get(route('exams.attempts.review', $attempt))
            ->assertSuccessful();

        $positions = [];
        foreach ($submitted->questions()->orderBy('position')->get() as $question) {
            $review->assertSee(__('exams.question_n', ['n' => $question->position], 'ja'), false);
            $positions[] = $question->position;
        }

        $this->assertSame([1, 2, 3], $positions);

        $this->actingAs($user)
            ->get(route('exams.attempts.results', $attempt))
            ->assertSuccessful();
    }

    public function test_illustrated_groups_remain_atomic_units_in_shuffled_order(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(2, 1, 4, 30);
        $this->publishedTrueFalse($examType, true);
        $this->publishedTrueFalse($examType, false);
        $illustrated = $this->publishedIllustrated($examType);

        $attempt = app(ExamAttemptService::class)->start($user, $examType, ExamAttemptType::Full);
        $illustratedRow = $attempt->questions->firstWhere('question_id', $illustrated->id);

        $this->assertNotNull($illustratedRow);
        $this->assertCount(3, $illustratedRow->content_snapshot['statements'] ?? []);
        $this->assertSame(
            [1, 2, 3],
            collect($illustratedRow->content_snapshot['statements'])->pluck('sort_order')->all()
        );
    }
}
