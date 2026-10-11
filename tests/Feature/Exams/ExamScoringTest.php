<?php

namespace Tests\Feature\Exams;

use App\Enums\ExamAttemptType;
use App\Models\ExamAttempt;
use App\Services\Exams\ExamAttemptService;
use App\Services\Exams\ExamScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamScoringTest extends TestCase
{
    use CreatesPracticeExams;
    use RefreshDatabase;

    public function test_standard_true_false_question_scores_one_point_when_correct(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(1, 0, 1);
        $this->publishedTrueFalse($examType, false);
        $service = app(ExamAttemptService::class);
        $attempt = $service->start($user, $examType, ExamAttemptType::Full);

        $service->recordAnswer($attempt, $attempt->questions->first()->id, 0, false);
        $final = $service->submit($attempt);

        $this->assertSame(1, $final->score);
        $this->assertSame(1, $final->max_score);
        $this->assertTrue($final->passed);
    }

    public function test_unanswered_question_scores_zero(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(1, 0, 1);
        $this->publishedTrueFalse($examType, true);
        $final = app(ExamAttemptService::class)->submit(
            app(ExamAttemptService::class)->start($user, $examType, ExamAttemptType::Full)
        );

        $this->assertSame(0, $final->score);
        $this->assertFalse($final->passed);
        $counts = app(ExamScoringService::class)->outcomeCounts($final->loadForDisplay());
        $this->assertSame(0, $counts['correct']);
        $this->assertSame(0, $counts['incorrect']);
        $this->assertSame(1, $counts['unanswered']);
    }

    public function test_illustrated_group_scores_two_points_only_when_every_statement_is_correct(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(0, 1, 2);
        $this->publishedIllustrated($examType, [true, false, true]);
        $service = app(ExamAttemptService::class);
        $attempt = $service->start($user, $examType, ExamAttemptType::Full);
        $attemptQuestion = $attempt->questions->first();

        $service->recordAnswer($attempt, $attemptQuestion->id, 1, true);
        $service->recordAnswer($attempt, $attemptQuestion->id, 2, false);
        $service->recordAnswer($attempt, $attemptQuestion->id, 3, true);
        $final = $service->submit($attempt->fresh());

        $this->assertSame(2, $final->score);
        $this->assertSame(2, $final->max_score);
        $this->assertTrue($final->passed);
        $this->assertSame(3, $attemptQuestion->answers()->count());
    }

    public function test_illustrated_group_scores_zero_when_any_statement_is_wrong(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(0, 1, 2);
        $this->publishedIllustrated($examType, [true, false, true]);
        $service = app(ExamAttemptService::class);
        $attempt = $service->start($user, $examType, ExamAttemptType::Full);
        $attemptQuestionId = $attempt->questions->first()->id;

        $service->recordAnswer($attempt, $attemptQuestionId, 1, true);
        $service->recordAnswer($attempt, $attemptQuestionId, 2, true);
        $service->recordAnswer($attempt, $attemptQuestionId, 3, true);
        $final = $service->submit($attempt->fresh());

        $this->assertSame(0, $final->score);
        $this->assertFalse($final->passed);
        $counts = app(ExamScoringService::class)->outcomeCounts($final->loadForDisplay());
        $this->assertSame(0, $counts['correct']);
        $this->assertSame(1, $counts['incorrect']);
        $this->assertSame(0, $counts['unanswered']);
    }

    public function test_passing_result_uses_the_attempt_threshold(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(2, 0, 2);
        $this->publishedTrueFalse($examType, true, ['japanese_text' => '一問目。']);
        $this->publishedTrueFalse($examType, true, ['japanese_text' => '二問目。']);
        $service = app(ExamAttemptService::class);
        $attempt = $service->start($user, $examType, ExamAttemptType::Full);
        $first = $attempt->questions->sortBy('position')->first();
        $service->recordAnswer($attempt, $first->id, 0, true);
        $final = $service->submit($attempt->fresh());

        $this->assertSame(1, $final->score);
        $this->assertSame(2, $final->passing_score);
        $this->assertFalse($final->passed);
        $this->assertInstanceOf(ExamAttempt::class, $final);
    }
}
