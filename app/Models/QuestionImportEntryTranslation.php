<?php

namespace App\Models;

use App\Enums\StagedTranslationStatus;
use App\Support\SafeRubyMarkup;
use App\Support\SupportedLocale;
use Database\Factories\QuestionImportEntryTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionImportEntryTranslation extends Model
{
    /** @use HasFactory<QuestionImportEntryTranslationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'question_import_entry_id',
        'locale',
        'question_text',
        'explanation',
        'figure_caption',
        'nuance_note',
        'japanese_reading_data',
        'status',
        'quality_flags',
        'translated_at',
        'reviewed_at',
        'translator_note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StagedTranslationStatus::class,
            'japanese_reading_data' => 'array',
            'quality_flags' => 'array',
            'translated_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (QuestionImportEntryTranslation $translation): void {
            $translation->locale = SupportedLocale::normalize($translation->locale);

            if ($translation->question_text !== null) {
                $translation->question_text = SafeRubyMarkup::plainText($translation->question_text);
            }

            if ($translation->explanation !== null) {
                $translation->explanation = SafeRubyMarkup::plainText($translation->explanation);
            }

            if ($translation->figure_caption !== null) {
                $translation->figure_caption = SafeRubyMarkup::plainText($translation->figure_caption);
            }

            if ($translation->nuance_note !== null) {
                $translation->nuance_note = SafeRubyMarkup::plainText($translation->nuance_note);
            }
        });
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(QuestionImportEntry::class, 'question_import_entry_id');
    }

    public function hasCompleteText(): bool
    {
        return filled($this->question_text) && filled($this->explanation);
    }
}
