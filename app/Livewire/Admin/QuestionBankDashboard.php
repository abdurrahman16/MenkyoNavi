<?php

namespace App\Livewire\Admin;

use App\Enums\AnswerKeyReviewStatus;
use App\Enums\BatchContentRightsStatus;
use App\Enums\ContentRightsReviewStatus;
use App\Enums\EditorialExamCategory;
use App\Enums\EditorialStudyTopic;
use App\Enums\EditorialWorkflowStatus;
use App\Models\QuestionImportBatch;
use App\Models\QuestionImportDuplicateReview;
use App\Models\QuestionImportEntry;
use App\Services\Editorial\ClassificationProposalService;
use App\Services\Editorial\EditorialReviewService;
use App\Services\Editorial\KarimenTest1AuditService;
use App\Services\Editorial\KarimenTest1PriorityReviewService;
use App\Services\Translations\QuestionBankReadinessReporter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Question bank editorial dashboard')]
class QuestionBankDashboard extends Component
{
    public string $statusMessage = '';

    public function mount(EditorialReviewService $editorial): void
    {
        $this->authorize('viewAny', QuestionImportEntry::class);
        $editorial->ensureSimilarPairReviewsSeeded();
    }

    public function generateProposals(ClassificationProposalService $proposals): void
    {
        $user = Auth::user();
        abort_unless($user !== null && $user->isAdministrator(), 403);
        $this->authorize('classify', new QuestionImportEntry);

        $examStats = $proposals->generateExamClassificationProposals($user);
        $topicStats = $proposals->generateStudyTopicProposals($user);

        $this->statusMessage = __('admin.bulk_proposals_generated', [
            'exam' => $examStats['updated'],
            'topics' => $topicStats['updated'],
            'skipped' => $examStats['skipped_approved'],
        ]);
    }

    public function exportKarimenTest1Audit(KarimenTest1AuditService $auditService): void
    {
        $this->authorize('viewAny', QuestionImportEntry::class);

        $audit = $auditService->audit();
        $paths = $auditService->writePrivateReports($audit);

        $this->statusMessage = __('admin.karimen_test_1_audit_exported', [
            'ready' => $audit['readiness']['fully_publication_ready_questions'],
            'json' => $paths['json_path'],
            'markdown' => $paths['markdown_path'],
        ]);
    }

    public function exportKarimenTest1PriorityReview(KarimenTest1PriorityReviewService $priorityReview): void
    {
        $this->authorize('viewAny', QuestionImportEntry::class);

        try {
            $report = $priorityReview->build();
            $paths = $priorityReview->writePrivateReports($report);
        } catch (\Throwable $exception) {
            $this->statusMessage = $exception->getMessage();

            return;
        }

        $this->statusMessage = __('admin.karimen_test_1_priority_review_exported', [
            'json' => $paths['json_path'],
            'markdown' => $paths['markdown_path'],
        ]);
    }

    public function render(QuestionBankReadinessReporter $reporter): View
    {
        $report = $reporter->report();

        $byFamily = QuestionImportEntry::query()
            ->selectRaw('exam_family, count(*) as aggregate')
            ->groupBy('exam_family')
            ->pluck('aggregate', 'exam_family')
            ->all();

        $byEditorialCategory = [];

        foreach (EditorialExamCategory::cases() as $category) {
            $byEditorialCategory[$category->value] = QuestionImportEntry::query()
                ->whereJsonContains('editorial_categories', $category->value)
                ->count();
        }

        $byStudyTopic = [];

        foreach (EditorialStudyTopic::cases() as $topic) {
            $count = QuestionImportEntry::query()
                ->whereJsonContains('study_topics', $topic->value)
                ->count();

            if ($count > 0) {
                $byStudyTopic[$topic->value] = $count;
            }
        }

        $draftPromotionReady = QuestionImportEntry::query()
            ->where('classification_approved', true)
            ->where('editorial_workflow_status', EditorialWorkflowStatus::ReadyForTranslation->value)
            ->count();

        $classificationStats = [
            'approved' => QuestionImportEntry::query()->where('classification_approved', true)->count(),
            'pending' => QuestionImportEntry::query()->where('classification_approved', false)->count(),
            'proposed_karimen' => QuestionImportEntry::query()->where('proposed_exam_category', 'karimen')->count(),
            'proposed_honmen' => QuestionImportEntry::query()->where('proposed_exam_category', 'honmen')->count(),
            'proposed_unclassified' => QuestionImportEntry::query()->where('proposed_exam_category', 'unclassified')->count(),
            'unclassified_second_source' => QuestionImportEntry::query()->where('exam_family', 'unspecified')->count(),
            'study_topics_assigned' => QuestionImportEntry::query()->whereNotNull('study_topics')->whereJsonLength('study_topics', '>', 0)->count(),
            'study_topics_approved' => QuestionImportEntry::query()->where('study_topics_approved', true)->count(),
            'pending_editorial_reviews' => QuestionImportEntry::query()
                ->whereIn('editorial_workflow_status', [
                    EditorialWorkflowStatus::NeedsReview->value,
                    EditorialWorkflowStatus::InReview->value,
                ])
                ->count(),
            'verified_answer_keys' => QuestionImportEntry::query()
                ->where('answer_key_status', AnswerKeyReviewStatus::Verified->value)
                ->count(),
            'disputed_answer_keys' => QuestionImportEntry::query()
                ->where('answer_key_status', AnswerKeyReviewStatus::Disputed->value)
                ->count(),
            'content_rights_approved' => QuestionImportEntry::query()
                ->where('content_rights_status', ContentRightsReviewStatus::ApprovedForPublicReuse->value)
                ->count(),
        ];

        $karimenPriorityReview = $this->buildKarimenTest1PrioritySummary();

        return view('livewire.admin.question-bank-dashboard', [
            'report' => $report,
            'karimenPriorityReview' => $karimenPriorityReview,
            'batches' => QuestionImportBatch::query()->orderBy('id')->get(['id', 'source_key', 'entry_count', 'source_filename']),
            'byFamily' => $byFamily,
            'byEditorialCategory' => $byEditorialCategory,
            'byStudyTopic' => $byStudyTopic,
            'classificationStats' => $classificationStats,
            'draftPromotionReady' => $draftPromotionReady,
            'similarPairsPending' => QuestionImportDuplicateReview::query()
                ->where('decision', 'needs_additional_review')
                ->count(),
        ]);
    }

    /**
     * @return array{
     *     priority_entries: Collection<int, QuestionImportEntry>,
     *     figure_dependent_entries: Collection<int, QuestionImportEntry>,
     *     batch: ?QuestionImportBatch,
     *     batch_rights_status: ?BatchContentRightsStatus
     * }
     */
    private function buildKarimenTest1PrioritySummary(): array
    {
        $sourceGroup = KarimenTest1PriorityReviewService::SOURCE_GROUP;

        $priorityEntries = QuestionImportEntry::query()
            ->where('source_group', $sourceGroup)
            ->whereIn('original_question_number', KarimenTest1PriorityReviewService::PRIORITY_QUESTION_NUMBERS)
            ->orderBy('original_question_number')
            ->get(['id', 'original_question_number', 'english_question', 'question_import_batch_id']);

        $figureDependentEntries = QuestionImportEntry::query()
            ->where('source_group', $sourceGroup)
            ->where(function ($query): void {
                $query->whereNotNull('sign_key')->where('sign_key', '!=', '')
                    ->orWhereNotNull('figure_caption')->where('figure_caption', '!=', '');
            })
            ->orderBy('original_question_number')
            ->get(['id', 'original_question_number', 'sign_key', 'figure_caption', 'question_import_batch_id']);

        $batchId = $priorityEntries->first()?->question_import_batch_id
            ?? $figureDependentEntries->first()?->question_import_batch_id;

        $batch = $batchId !== null
            ? QuestionImportBatch::query()->find($batchId)
            : null;

        $batchStatus = $batch?->content_rights_status instanceof BatchContentRightsStatus
            ? $batch->content_rights_status
            : ($batch !== null
                ? BatchContentRightsStatus::tryFrom((string) $batch->content_rights_status)
                : null);

        return [
            'priority_entries' => $priorityEntries,
            'figure_dependent_entries' => $figureDependentEntries,
            'batch' => $batch,
            'batch_rights_status' => $batchStatus,
        ];
    }
}
