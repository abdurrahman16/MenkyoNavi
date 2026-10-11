<?php

namespace App\Models;

use App\Support\SafeRubyMarkup;
use Database\Factories\QuestionStatementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionStatement extends Model
{
    /** @use HasFactory<QuestionStatementFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'question_id',
        'sort_order',
        'japanese_text',
        'japanese_reading_data',
        'correct_answer',
        'explanation_ja',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'japanese_reading_data' => 'array',
            'correct_answer' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (QuestionStatement $statement): void {
            $statement->japanese_text = SafeRubyMarkup::plainText($statement->japanese_text);

            if ($statement->explanation_ja !== null) {
                $statement->explanation_ja = SafeRubyMarkup::plainText($statement->explanation_ja);
            }
        });
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(QuestionStatementTranslation::class);
    }
}
