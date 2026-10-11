<?php

namespace Database\Factories;

use App\Enums\DuplicatePairDecision;
use App\Models\QuestionImportDuplicateReview;
use App\Models\QuestionImportEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionImportDuplicateReview>
 */
class QuestionImportDuplicateReviewFactory extends Factory
{
    protected $model = QuestionImportDuplicateReview::class;

    public function definition(): array
    {
        return [
            'entry_id' => QuestionImportEntry::factory(),
            'related_entry_id' => QuestionImportEntry::factory(),
            'similarity_reason' => 'similar_wording_needs_review',
            'decision' => DuplicatePairDecision::NeedsAdditionalReview,
            'reviewed_by' => null,
            'notes' => null,
            'reviewed_at' => null,
        ];
    }
}
