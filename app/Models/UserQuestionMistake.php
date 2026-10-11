<?php

namespace App\Models;

use Database\Factories\UserQuestionMistakeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserQuestionMistake extends Model
{
    /** @use HasFactory<UserQuestionMistakeFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'question_id',
        'exam_type_id',
        'last_exam_attempt_id',
        'incorrect_count',
        'last_incorrect_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'incorrect_count' => 'integer',
            'last_incorrect_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function examType(): BelongsTo
    {
        return $this->belongsTo(ExamType::class);
    }

    public function lastExamAttempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'last_exam_attempt_id');
    }
}
