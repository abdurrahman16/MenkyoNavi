<?php

namespace Database\Factories;

use App\Enums\BatchContentRightsStatus;
use App\Enums\QuestionImportStatus;
use App\Models\QuestionImportBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionImportBatch>
 */
class QuestionImportBatchFactory extends Factory
{
    protected $model = QuestionImportBatch::class;

    public function definition(): array
    {
        return [
            'source_key' => 'test_source_'.fake()->unique()->lexify('??????'),
            'source_filename' => 'karimen_exam_trainer.html',
            'source_relative_path' => 'menkyo-imports/karimen_exam_trainer.html',
            'file_checksum' => hash('sha256', fake()->uuid()),
            'source_locale' => 'en',
            'status' => QuestionImportStatus::Staged,
            'entry_count' => 0,
            'parse_error_count' => 0,
            'duplicate_across_sets_count' => 0,
            'same_wording_different_answer_count' => 0,
            'flagged_for_review_count' => 0,
            'media_review_count' => 0,
            'group_counts' => [],
            'parse_errors' => [],
            'summary' => [],
            'imported_at' => now(),
            'content_rights_status' => BatchContentRightsStatus::NoEvidenceSubmitted,
            'claimed_creator' => null,
            'origin_url' => null,
            'acquisition_notes' => null,
            'license_notes' => null,
            'permission_notes' => null,
            'reuse_scope_notes' => null,
            'figure_rights_notes' => null,
            'rights_evidence' => null,
            'rights_document_path' => null,
            'rights_document_original_name' => null,
            'content_rights_reviewed_by' => null,
            'content_rights_reviewed_at' => null,
        ];
    }
}
