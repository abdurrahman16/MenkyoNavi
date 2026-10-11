<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionStatement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionStatement>
 */
class QuestionStatementFactory extends Factory
{
    protected $model = QuestionStatement::class;

    public function definition(): array
    {
        return [
            'question_id' => Question::factory()->illustrated(),
            'sort_order' => 1,
            'japanese_text' => '歩行者が渡っているときは待つ。',
            'japanese_reading_data' => null,
            'correct_answer' => true,
            'explanation_ja' => null,
        ];
    }
}
