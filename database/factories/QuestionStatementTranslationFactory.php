<?php

namespace Database\Factories;

use App\Enums\TranslationReviewStatus;
use App\Models\QuestionStatement;
use App\Models\QuestionStatementTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionStatementTranslation>
 */
class QuestionStatementTranslationFactory extends Factory
{
    protected $model = QuestionStatementTranslation::class;

    public function definition(): array
    {
        return [
            'question_statement_id' => QuestionStatement::factory(),
            'locale' => 'en',
            'statement_text' => 'Wait while pedestrians are crossing.',
            'explanation' => null,
            'nuance_note' => null,
            'review_status' => TranslationReviewStatus::Pending,
        ];
    }
}
