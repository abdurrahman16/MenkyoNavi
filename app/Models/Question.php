<?php

namespace App\Models;

use App\Enums\ContentReviewStatus;
use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Support\SafeRubyMarkup;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'type',
        'status',
        'difficulty',
        'japanese_text',
        'japanese_reading_data',
        'correct_answer',
        'explanation_ja',
        'question_category_id',
        'source_reference_id',
        'source_page_number',
        'content_review_status',
        'reviewed_at',
        'is_demo',
        'points',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'status' => QuestionStatus::class,
            'content_review_status' => ContentReviewStatus::class,
            'japanese_reading_data' => 'array',
            'correct_answer' => 'boolean',
            'is_demo' => 'boolean',
            'difficulty' => 'integer',
            'points' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Question $question): void {
            if (blank($question->uuid)) {
                $question->uuid = (string) Str::uuid();
            }

            $question->japanese_text = SafeRubyMarkup::plainText($question->japanese_text);
        });

        static::updating(function (Question $question): void {
            if ($question->isDirty('japanese_text')) {
                $question->japanese_text = SafeRubyMarkup::plainText($question->japanese_text);
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(QuestionCategory::class, 'question_category_id');
    }

    public function sourceReference(): BelongsTo
    {
        return $this->belongsTo(SourceReference::class);
    }

    public function examTypes(): BelongsToMany
    {
        return $this->belongsToMany(ExamType::class)->withTimestamps();
    }

    public function translations(): HasMany
    {
        return $this->hasMany(QuestionTranslation::class);
    }

    public function statements(): HasMany
    {
        return $this->hasMany(QuestionStatement::class)->orderBy('sort_order');
    }

    public function media(): HasMany
    {
        return $this->hasMany(QuestionMedia::class)->orderBy('sort_order');
    }

    public function translationFor(string $locale): ?QuestionTranslation
    {
        return $this->translations->firstWhere('locale', $locale);
    }

    public function japaneseWithRuby(): string
    {
        if (is_array($this->japanese_reading_data) && $this->japanese_reading_data !== []) {
            return SafeRubyMarkup::fromReadingData($this->japanese_reading_data);
        }

        return e($this->japanese_text);
    }

    public function isPublished(): bool
    {
        return $this->status === QuestionStatus::Published;
    }

    public function isIllustratedGroup(): bool
    {
        return $this->type === QuestionType::IllustratedGroup;
    }
}
