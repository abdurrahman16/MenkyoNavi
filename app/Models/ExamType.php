<?php

namespace App\Models;

use Database\Factories\ExamTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamType extends Model
{
    /** @use HasFactory<ExamTypeFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'name_ja',
        'description',
        'selection_rules',
        'duration_minutes',
        'max_score',
        'passing_score',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'selection_rules' => 'array',
            'duration_minutes' => 'integer',
            'max_score' => 'integer',
            'passing_score' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class)->withTimestamps();
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function standardQuestionCount(): int
    {
        return max(0, (int) ($this->selection_rules['standard_true_false_count'] ?? 0));
    }

    public function illustratedGroupCount(): int
    {
        return max(0, (int) ($this->selection_rules['illustrated_group_count'] ?? 0));
    }

    public function pointsPerStandardQuestion(): int
    {
        return max(0, (int) ($this->selection_rules['points_per_standard_question'] ?? 1));
    }

    public function pointsPerIllustratedGroup(): int
    {
        return max(0, (int) ($this->selection_rules['points_per_illustrated_group'] ?? 2));
    }

    public function statementsPerIllustratedGroup(): int
    {
        return max(1, (int) ($this->selection_rules['statements_per_illustrated_group'] ?? 3));
    }

    public function usesAllOrNothingIllustratedScoring(): bool
    {
        return ($this->selection_rules['illustrated_scoring'] ?? 'all_or_nothing') === 'all_or_nothing';
    }
}
