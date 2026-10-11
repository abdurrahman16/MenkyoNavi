<?php

namespace App\Models;

use Database\Factories\QuestionImportReviewAuditFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionImportReviewAudit extends Model
{
    /** @use HasFactory<QuestionImportReviewAuditFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'question_import_entry_id',
        'user_id',
        'action',
        'before',
        'after',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(QuestionImportEntry::class, 'question_import_entry_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
