<?php

namespace App\Models;

use App\Enums\BatchContentRightsStatus;
use App\Enums\QuestionImportStatus;
use Database\Factories\QuestionImportBatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class QuestionImportBatch extends Model
{
    /** @use HasFactory<QuestionImportBatchFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'content_rights_status' => BatchContentRightsStatus::NoEvidenceSubmitted->value,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'source_key',
        'source_filename',
        'source_relative_path',
        'file_checksum',
        'source_locale',
        'status',
        'entry_count',
        'parse_error_count',
        'duplicate_across_sets_count',
        'same_wording_different_answer_count',
        'flagged_for_review_count',
        'media_review_count',
        'group_counts',
        'parse_errors',
        'summary',
        'imported_at',
        'content_rights_status',
        'claimed_creator',
        'origin_url',
        'acquisition_notes',
        'license_notes',
        'permission_notes',
        'reuse_scope_notes',
        'figure_rights_notes',
        'rights_evidence',
        'rights_document_path',
        'rights_document_original_name',
        'content_rights_reviewed_by',
        'content_rights_reviewed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => QuestionImportStatus::class,
            'entry_count' => 'integer',
            'parse_error_count' => 'integer',
            'duplicate_across_sets_count' => 'integer',
            'same_wording_different_answer_count' => 'integer',
            'flagged_for_review_count' => 'integer',
            'media_review_count' => 'integer',
            'group_counts' => 'array',
            'parse_errors' => 'array',
            'summary' => 'array',
            'imported_at' => 'datetime',
            'content_rights_status' => BatchContentRightsStatus::class,
            'rights_evidence' => 'array',
            'content_rights_reviewed_at' => 'datetime',
        ];
    }

    public function contentRightsReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'content_rights_reviewed_by');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(QuestionImportEntry::class);
    }

    public function entryTranslations(): HasManyThrough
    {
        return $this->hasManyThrough(
            QuestionImportEntryTranslation::class,
            QuestionImportEntry::class,
        );
    }
}
