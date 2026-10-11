<?php

namespace App\Models;

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamAttemptType;
use Database\Factories\ExamAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ExamAttempt extends Model
{
    /** @use HasFactory<ExamAttemptFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'exam_type_id',
        'attempt_type',
        'status',
        'started_at',
        'expires_at',
        'submitted_at',
        'question_count',
        'max_score',
        'score',
        'passing_score',
        'passed',
        'duration_seconds',
        'current_position',
        'counts_toward_analytics',
        'locale',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attempt_type' => ExamAttemptType::class,
            'status' => ExamAttemptStatus::class,
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
            'question_count' => 'integer',
            'max_score' => 'integer',
            'score' => 'integer',
            'passing_score' => 'integer',
            'passed' => 'boolean',
            'duration_seconds' => 'integer',
            'current_position' => 'integer',
            'counts_toward_analytics' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ExamAttempt $attempt): void {
            if (blank($attempt->uuid)) {
                $attempt->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function resolveRouteBinding($value, $field = null): ExamAttempt
    {
        $userId = auth()->id();

        if ($userId === null) {
            abort(404);
        }

        return $this->newQuery()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->where('user_id', $userId)
            ->firstOrFail();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function examType(): BelongsTo
    {
        return $this->belongsTo(ExamType::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ExamAttemptQuestion::class)->orderBy('position')->orderBy('id');
    }

    public function isDemo(): bool
    {
        return $this->attempt_type === ExamAttemptType::Demo;
    }

    public function isAdminPreview(): bool
    {
        return $this->attempt_type === ExamAttemptType::AdminPreview;
    }

    public function isOpen(): bool
    {
        return $this->status === ExamAttemptStatus::InProgress && $this->expires_at->isFuture();
    }

    public function scorePercentage(): int
    {
        if ($this->max_score < 1 || $this->score === null) {
            return 0;
        }

        return (int) round(($this->score / $this->max_score) * 100);
    }

    public function formattedDuration(): string
    {
        $seconds = max(0, (int) $this->duration_seconds);
        $minutes = intdiv($seconds, 60);
        $remainder = $seconds % 60;

        return sprintf('%d:%02d', $minutes, $remainder);
    }

    public function loadForDisplay(): self
    {
        $this->load([
            'examType',
            'questions' => fn ($query) => $query->orderBy('position')->orderBy('id'),
            'questions.answers' => fn ($query) => $query->orderBy('statement_slot')->orderBy('id'),
        ]);

        return $this;
    }
}
