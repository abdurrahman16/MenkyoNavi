<?php

namespace App\Models;

use App\Enums\AccuracyEvidenceStatus;
use App\Enums\AnswerKeyReviewStatus;
use App\Enums\ContentRightsReviewStatus;
use App\Enums\EditorialWorkflowStatus;
use App\Enums\FigureReviewStatus;
use App\Enums\QuestionImportDuplicateKind;
use App\Enums\QuestionImportMediaStatus;
use App\Enums\QuestionImportStatus;
use App\Enums\StagedTranslationStatus;
use Database\Factories\QuestionImportEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionImportEntry extends Model
{
    /** @use HasFactory<QuestionImportEntryFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'question_import_batch_id',
        'source_group',
        'exam_family',
        'editorial_categories',
        'proposed_exam_category',
        'classification_proposal_source',
        'study_topics',
        'study_topics_approved',
        'classification_approved',
        'classification_approved_by',
        'classification_approved_at',
        'editorial_workflow_status',
        'editorial_notes',
        'original_question_number',
        'english_question',
        'correct_answer',
        'english_explanation',
        'sign_key',
        'figure_caption',
        'figure_review_status',
        'figure_review_notes',
        'source_entry_checksum',
        'content_fingerprint',
        'question_text_fingerprint',
        'import_status',
        'media_availability',
        'duplicate_kind',
        'duplicate_of_entry_id',
        'review_flags',
        'source_metadata',
        'is_published',
        'answer_key_status',
        'answer_key_notes',
        'answer_key_reference',
        'answer_key_verified_at',
        'accuracy_evidence_status',
        'legal_topic',
        'accuracy_evidence_sources',
        'accuracy_verification_notes',
        'accuracy_reviewed_by',
        'accuracy_reviewed_at',
        'proposed_correct_answer',
        'proposed_answer_notes',
        'content_rights_status',
        'content_rights_notes',
        'content_rights_evidence',
        'content_rights_reviewed_by',
        'content_rights_reviewed_at',
        'editorial_issues',
        'publication_blockers',
        'promoted_question_id',
        'promoted_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'answer_key_status' => 'verification_pending',
        'content_rights_status' => 'unclear',
        'accuracy_evidence_status' => 'not_checked',
        'figure_review_status' => 'not_applicable',
        'is_published' => false,
        'classification_approved' => false,
        'study_topics_approved' => false,
        'editorial_workflow_status' => 'needs_review',
    ];

    /**
     * Original English source fields that editorial tools must never rewrite.
     *
     * @var list<string>
     */
    public const IMMUTABLE_SOURCE_FIELDS = [
        'english_question',
        'correct_answer',
        'english_explanation',
        'sign_key',
        'figure_caption',
        'source_group',
        'original_question_number',
        'exam_family',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'original_question_number' => 'integer',
            'correct_answer' => 'boolean',
            'editorial_categories' => 'array',
            'study_topics' => 'array',
            'study_topics_approved' => 'boolean',
            'classification_approved' => 'boolean',
            'classification_approved_at' => 'datetime',
            'editorial_workflow_status' => EditorialWorkflowStatus::class,
            'import_status' => QuestionImportStatus::class,
            'media_availability' => QuestionImportMediaStatus::class,
            'duplicate_kind' => QuestionImportDuplicateKind::class,
            'review_flags' => 'array',
            'source_metadata' => 'array',
            'is_published' => 'boolean',
            'answer_key_status' => AnswerKeyReviewStatus::class,
            'answer_key_verified_at' => 'datetime',
            'accuracy_evidence_status' => AccuracyEvidenceStatus::class,
            'accuracy_evidence_sources' => 'array',
            'accuracy_reviewed_at' => 'datetime',
            'figure_review_status' => FigureReviewStatus::class,
            'proposed_correct_answer' => 'boolean',
            'content_rights_status' => ContentRightsReviewStatus::class,
            'content_rights_reviewed_at' => 'datetime',
            'editorial_issues' => 'array',
            'publication_blockers' => 'array',
            'promoted_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(QuestionImportBatch::class, 'question_import_batch_id');
    }

    public function duplicateOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'duplicate_of_entry_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(QuestionImportEntryTranslation::class);
    }

    public function reviewAudits(): HasMany
    {
        return $this->hasMany(QuestionImportReviewAudit::class);
    }

    public function classificationApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'classification_approved_by');
    }

    public function contentRightsReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'content_rights_reviewed_by');
    }

    public function accuracyReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accuracy_reviewed_by');
    }

    public function promotedQuestion(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'promoted_question_id');
    }

    public function stableSourceId(): string
    {
        return $this->source_group.':'.$this->original_question_number;
    }

    public function isFigureDependent(): bool
    {
        return filled($this->sign_key) || filled($this->figure_caption);
    }

    public function translationFor(string $locale): ?QuestionImportEntryTranslation
    {
        if ($this->relationLoaded('translations')) {
            return $this->translations->firstWhere('locale', $locale);
        }

        return $this->translations()->where('locale', $locale)->first();
    }

    /**
     * Tracking / status labels that must never block publication by themselves.
     *
     * @var list<string>
     */
    public const INFORMATIONAL_REVIEW_FLAGS = [
        'original_menkyo_pilot',
        'original_menkyo_expansion',
        'task_44_accuracy_audited',
        'task_48_expansion',
        'batch_a',
        'batch_b',
        'batch_c',
        'batch_d',
        'human_accuracy_accepted',
        'human_editorial_approved',
        'japanese_ready_for_publication',
        'source_revision_applied',
        'source_revision_rejected',
        'answer_key_verified_flag',
        'rights_cleared_flag',
    ];

    /**
     * @return list<string>
     */
    public function blockingReviewFlags(): array
    {
        return collect($this->review_flags ?? [])
            ->filter(fn (mixed $flag): bool => is_string($flag) && $flag !== '')
            ->reject(function (string $flag): bool {
                if (in_array($flag, self::INFORMATIONAL_REVIEW_FLAGS, true)) {
                    return true;
                }

                return str_starts_with($flag, 'batch_');
            })
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public function computePublicationBlockers(): array
    {
        $blockers = [];

        if ($this->content_rights_status->blocksPublication()) {
            $blockers[] = 'content_rights_'.$this->content_rights_status->value;
        }

        if ($this->answer_key_status->blocksPublication()) {
            $blockers[] = 'answer_key_'.$this->answer_key_status->value;
        }

        if ($this->accuracy_evidence_status->blocksPublicationReadiness()) {
            $blockers[] = 'accuracy_evidence_'.$this->accuracy_evidence_status->value;
        }

        if ($this->isFigureDependent() && ! $this->figure_review_status->isReadyForPublication()) {
            $blockers[] = 'figure_review_'.$this->figure_review_status->value;
        }

        if ($this->blockingReviewFlags() !== []) {
            $blockers[] = 'import_review_flags';
        }

        if (($this->editorial_issues ?? []) !== []) {
            $blockers[] = 'editorial_issues';
        }

        $japanese = $this->translationFor('ja');

        if ($japanese === null || ! $japanese->hasCompleteText()) {
            $blockers[] = 'missing_japanese_translation';
        } elseif (! $japanese->status->isReviewedOrHigher()) {
            $blockers[] = 'japanese_translation_unreviewed';
        } elseif ($japanese->status !== StagedTranslationStatus::ReadyForPublication) {
            $blockers[] = 'japanese_not_ready_for_publication';
        }

        $english = $this->translationFor('en');

        if ($english === null || ! $english->hasCompleteText()) {
            $blockers[] = 'missing_english_translation_record';
        }

        if ($this->is_published) {
            $blockers[] = 'already_marked_published_on_staging';
        }

        return array_values(array_unique($blockers));
    }

    public function isPublicationReady(): bool
    {
        return $this->computePublicationBlockers() === [];
    }
}
