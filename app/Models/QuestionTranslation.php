<?php

namespace App\Models;

use App\Enums\TranslationReviewStatus;
use App\Support\SafeRubyMarkup;
use App\Support\SupportedLocale;
use Database\Factories\QuestionTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionTranslation extends Model
{
    /** @use HasFactory<QuestionTranslationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'question_id',
        'locale',
        'question_text',
        'explanation',
        'nuance_note',
        'review_status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'review_status' => TranslationReviewStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (QuestionTranslation $translation): void {
            $translation->locale = SupportedLocale::normalize($translation->locale);
            $translation->question_text = SafeRubyMarkup::plainText($translation->question_text);

            if ($translation->explanation !== null) {
                $translation->explanation = SafeRubyMarkup::plainText($translation->explanation);
            }

            if ($translation->nuance_note !== null) {
                $translation->nuance_note = SafeRubyMarkup::plainText($translation->nuance_note);
            }
        });
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
