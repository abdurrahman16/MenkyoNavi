<?php

namespace App\Models;

use App\Enums\ExamScoringMode;
use App\Enums\QuestionType;
use Database\Factories\ExamAttemptQuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAttemptQuestion extends Model
{
    /** @use HasFactory<ExamAttemptQuestionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'exam_attempt_id',
        'question_id',
        'question_uuid',
        'position',
        'question_type',
        'scoring_mode',
        'scoring_weight',
        'points_awarded',
        'is_correct',
        'content_snapshot',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'question_type' => QuestionType::class,
            'scoring_mode' => ExamScoringMode::class,
            'position' => 'integer',
            'scoring_weight' => 'integer',
            'points_awarded' => 'integer',
            'is_correct' => 'boolean',
            'content_snapshot' => 'array',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAttemptAnswer::class)->orderBy('statement_slot')->orderBy('id');
    }

    public function isIllustratedGroup(): bool
    {
        return $this->scoring_mode === ExamScoringMode::AllOrNothing;
    }
}
