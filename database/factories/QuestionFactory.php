<?php

namespace Database\Factories;

use App\Enums\ContentReviewStatus;
use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\QuestionCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'type' => QuestionType::StandardTrueFalse,
            'status' => QuestionStatus::Draft,
            'difficulty' => 1,
            'japanese_text' => '一時停止の標識があるときは、必ず止まらなければならない。',
            'japanese_reading_data' => null,
            'correct_answer' => true,
            'explanation_ja' => '一時停止では完全に停止する必要がある。',
            'question_category_id' => QuestionCategory::factory(),
            'source_reference_id' => null,
            'source_page_number' => null,
            'content_review_status' => ContentReviewStatus::Pending,
            'reviewed_at' => null,
            'is_demo' => false,
            'points' => 1,
        ];
    }

    public function demo(): static
    {
        return $this->state(fn (): array => [
            'status' => QuestionStatus::Demo,
            'is_demo' => true,
        ]);
    }

    public function illustrated(): static
    {
        return $this->state(fn (): array => [
            'type' => QuestionType::IllustratedGroup,
            'correct_answer' => null,
            'points' => 2,
        ]);
    }
}
