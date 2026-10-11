<?php

namespace Database\Factories;

use App\Enums\StagedTranslationStatus;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportEntryTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionImportEntryTranslation>
 */
class QuestionImportEntryTranslationFactory extends Factory
{
    protected $model = QuestionImportEntryTranslation::class;

    public function definition(): array
    {
        return [
            'question_import_entry_id' => QuestionImportEntry::factory(),
            'locale' => 'ja',
            'question_text' => '一時停止の標識があるときは、必ず止まらなければならない。',
            'explanation' => '一時停止では完全に停止する必要がある。',
            'figure_caption' => null,
            'nuance_note' => null,
            'japanese_reading_data' => null,
            'status' => StagedTranslationStatus::Draft,
            'quality_flags' => [],
            'translated_at' => now(),
            'reviewed_at' => null,
            'translator_note' => 'Factory draft',
        ];
    }

    public function reviewed(): static
    {
        return $this->state(fn (): array => [
            'status' => StagedTranslationStatus::Reviewed,
            'reviewed_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => StagedTranslationStatus::PendingTranslation,
            'question_text' => null,
            'explanation' => null,
            'translated_at' => null,
        ]);
    }
}
