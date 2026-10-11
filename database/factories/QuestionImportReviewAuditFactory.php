<?php

namespace Database\Factories;

use App\Models\QuestionImportEntry;
use App\Models\QuestionImportReviewAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionImportReviewAudit>
 */
class QuestionImportReviewAuditFactory extends Factory
{
    protected $model = QuestionImportReviewAudit::class;

    public function definition(): array
    {
        return [
            'question_import_entry_id' => QuestionImportEntry::factory(),
            'user_id' => User::factory()->administrator(),
            'action' => 'classification_updated',
            'before' => ['editorial_categories' => null],
            'after' => ['editorial_categories' => ['karimen']],
            'notes' => fake()->sentence(),
        ];
    }
}
