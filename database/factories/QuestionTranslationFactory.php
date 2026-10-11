<?php

namespace Database\Factories;

use App\Enums\TranslationReviewStatus;
use App\Models\Question;
use App\Models\QuestionTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionTranslation>
 */
class QuestionTranslationFactory extends Factory
{
    protected $model = QuestionTranslation::class;

    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'locale' => 'en',
            'question_text' => 'You must come to a complete stop at a stop sign.',
            'explanation' => 'A rolling pause is not enough.',
            'nuance_note' => null,
            'review_status' => TranslationReviewStatus::Pending,
        ];
    }
}
