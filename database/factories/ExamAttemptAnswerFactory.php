<?php

namespace Database\Factories;

use App\Models\ExamAttemptAnswer;
use App\Models\ExamAttemptQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamAttemptAnswer>
 */
class ExamAttemptAnswerFactory extends Factory
{
    protected $model = ExamAttemptAnswer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_attempt_question_id' => ExamAttemptQuestion::factory(),
            'statement_slot' => 0,
            'question_statement_id' => null,
            'selected_answer' => true,
            'is_correct' => true,
            'points_awarded' => 1,
            'answered_at' => now(),
        ];
    }
}
