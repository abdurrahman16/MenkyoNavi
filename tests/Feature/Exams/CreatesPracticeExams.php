<?php

namespace Tests\Feature\Exams;

use App\Enums\ContentReviewStatus;
use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Models\ExamAttempt;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\QuestionStatement;
use App\Models\User;
use App\Services\Exams\ExamAttemptService;

trait CreatesPracticeExams
{
    protected function examTypeWithRules(int $standard, int $illustrated, int $passingScore, int $minutes = 30): ExamType
    {
        $maxScore = ($standard * 1) + ($illustrated * 2);

        return ExamType::factory()->create([
            'selection_rules' => [
                'standard_true_false_count' => $standard,
                'illustrated_group_count' => $illustrated,
                'points_per_standard_question' => 1,
                'points_per_illustrated_group' => 2,
                'statements_per_illustrated_group' => 3,
                'illustrated_scoring' => 'all_or_nothing',
                'answer_style' => 'true_false',
            ],
            'duration_minutes' => $minutes,
            'max_score' => $maxScore,
            'passing_score' => $passingScore,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function publishedTrueFalse(ExamType $examType, bool $answer = true, array $overrides = []): Question
    {
        $question = Question::factory()->create(array_merge([
            'status' => QuestionStatus::Published,
            'content_review_status' => ContentReviewStatus::Reviewed,
            'reviewed_at' => now(),
            'is_demo' => false,
            'type' => QuestionType::StandardTrueFalse,
            'correct_answer' => $answer,
            'points' => 1,
            'explanation_ja' => 'Stop completely at the sign.',
        ], $overrides));
        $question->examTypes()->attach($examType->id);

        return $question;
    }

    /**
     * @param  list<bool>  $answers
     */
    protected function publishedIllustrated(ExamType $examType, array $answers = [true, false, true]): Question
    {
        $question = Question::factory()->illustrated()->create([
            'status' => QuestionStatus::Published,
            'content_review_status' => ContentReviewStatus::Reviewed,
            'reviewed_at' => now(),
            'is_demo' => false,
            'correct_answer' => null,
            'points' => 2,
            'explanation_ja' => 'Group explanation.',
        ]);

        foreach ($answers as $index => $answer) {
            QuestionStatement::factory()->create([
                'question_id' => $question->id,
                'sort_order' => $index + 1,
                'correct_answer' => $answer,
                'explanation_ja' => 'Statement '.($index + 1).' explanation.',
            ]);
        }

        $question->examTypes()->attach($examType->id);

        return $question;
    }

    protected function learner(): User
    {
        return User::factory()->create();
    }

    /**
     * Answer the current one-way unit (standard T/F) using attempt position order.
     *
     * @return array<string, mixed>
     */
    protected function answerCurrentStandard(ExamAttemptService $service, ExamAttempt $attempt, bool $selected): array
    {
        $attempt = $attempt->fresh()->loadForDisplay();
        $current = $attempt->questions->firstWhere('position', $attempt->current_position);

        return $service->recordAnswer($attempt, $current->id, 0, $selected);
    }
}
