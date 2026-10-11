<?php

namespace Database\Factories;

use App\Enums\ExamScoringMode;
use App\Enums\QuestionType;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptQuestion;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ExamAttemptQuestion>
 */
class ExamAttemptQuestionFactory extends Factory
{
    protected $model = ExamAttemptQuestion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_attempt_id' => ExamAttempt::factory(),
            'question_id' => Question::factory(),
            'question_uuid' => (string) Str::uuid(),
            'position' => 1,
            'question_type' => QuestionType::StandardTrueFalse,
            'scoring_mode' => ExamScoringMode::Standard,
            'scoring_weight' => 1,
            'points_awarded' => null,
            'is_correct' => null,
            'content_snapshot' => [
                'japanese_text' => '一時停止する。',
                'japanese_reading_data' => null,
                'explanation_ja' => null,
                'correct_answer' => true,
                'category_name' => null,
                'translations' => [],
                'statements' => [],
                'media' => [],
                'is_demo' => false,
            ],
        ];
    }
}
