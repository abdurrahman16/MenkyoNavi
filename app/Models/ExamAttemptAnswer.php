<?php

namespace App\Models;

use Database\Factories\ExamAttemptAnswerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamAttemptAnswer extends Model
{
    /** @use HasFactory<ExamAttemptAnswerFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'exam_attempt_question_id',
        'statement_slot',
        'question_statement_id',
        'selected_answer',
        'is_correct',
        'points_awarded',
        'answered_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'statement_slot' => 'integer',
            'selected_answer' => 'boolean',
            'is_correct' => 'boolean',
            'points_awarded' => 'integer',
            'answered_at' => 'datetime',
        ];
    }

    public function attemptQuestion(): BelongsTo
    {
        return $this->belongsTo(ExamAttemptQuestion::class, 'exam_attempt_question_id');
    }

    public function statement(): BelongsTo
    {
        return $this->belongsTo(QuestionStatement::class, 'question_statement_id');
    }
}
