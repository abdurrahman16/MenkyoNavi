<?php

namespace App\Models;

use App\Enums\TranslationReviewStatus;
use App\Support\SafeRubyMarkup;
use App\Support\SupportedLocale;
use Database\Factories\QuestionStatementTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionStatementTranslation extends Model
{
    /** @use HasFactory<QuestionStatementTranslationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'question_statement_id',
        'locale',
        'statement_text',
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
        static::saving(function (QuestionStatementTranslation $translation): void {
            $translation->locale = SupportedLocale::normalize($translation->locale);
            $translation->statement_text = SafeRubyMarkup::plainText($translation->statement_text);

            if ($translation->explanation !== null) {
                $translation->explanation = SafeRubyMarkup::plainText($translation->explanation);
            }

            if ($translation->nuance_note !== null) {
                $translation->nuance_note = SafeRubyMarkup::plainText($translation->nuance_note);
            }
        });
    }

    public function statement(): BelongsTo
    {
        return $this->belongsTo(QuestionStatement::class, 'question_statement_id');
    }
}
