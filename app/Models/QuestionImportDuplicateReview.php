<?php

namespace App\Models;

use App\Enums\DuplicatePairDecision;
use Database\Factories\QuestionImportDuplicateReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionImportDuplicateReview extends Model
{
    /** @use HasFactory<QuestionImportDuplicateReviewFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'entry_id',
        'related_entry_id',
        'similarity_reason',
        'decision',
        'reviewed_by',
        'notes',
        'reviewed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decision' => DuplicatePairDecision::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(QuestionImportEntry::class, 'entry_id');
    }

    public function relatedEntry(): BelongsTo
    {
        return $this->belongsTo(QuestionImportEntry::class, 'related_entry_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
