<?php

namespace Database\Factories;

use App\Models\ExamType;
use App\Models\Question;
use App\Models\User;
use App\Models\UserQuestionMistake;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserQuestionMistake>
 */
class UserQuestionMistakeFactory extends Factory
{
    protected $model = UserQuestionMistake::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'question_id' => Question::factory(),
            'exam_type_id' => ExamType::factory(),
            'last_exam_attempt_id' => null,
            'incorrect_count' => 1,
            'last_incorrect_at' => now(),
        ];
    }
}
