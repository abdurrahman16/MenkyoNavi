<?php

namespace App\Livewire\Admin;

use App\Enums\EditorialExamCategory;
use App\Enums\EditorialStudyTopic;
use App\Enums\EditorialWorkflowStatus;
use App\Models\QuestionImportBatch;
use App\Models\QuestionImportEntry;
use App\Models\User;
use App\Services\Editorial\EditorialReviewService;
use App\Services\Translations\StagedTranslationExportService;
use App\Support\SupportedLocale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Staged questions')]
class QuestionBankIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $sourceKey = '';

    #[Url]
    public string $sourceGroup = '';

    #[Url]
    public string $examFamily = '';

    #[Url]
    public string $workflow = '';

    #[Url]
    public string $editorialCategory = '';

    #[Url]
    public string $studyTopic = '';

    #[Url]
    public string $proposedCategory = '';

    #[Url]
    public string $missingTranslation = '';

    #[Url]
    public string $classificationState = '';

    public string $bulkTopic = '';

    public string $bulkNotes = '';

    public string $exportLocale = 'ja';

    public int $exportLimit = 25;

    public bool $confirmBulkAction = false;

    public string $statusMessage = '';

    /** @var list<int> */
    public array $selectedIds = [];

    public function mount(): void
    {
        $this->authorize('viewAny', QuestionImportEntry::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSourceKey(): void
    {
        $this->resetPage();
    }

    public function updatingSourceGroup(): void
    {
        $this->resetPage();
    }

    public function updatingExamFamily(): void
    {
        $this->resetPage();
    }

    public function updatingWorkflow(): void
    {
        $this->resetPage();
    }

    public function updatingEditorialCategory(): void
    {
        $this->resetPage();
    }

    public function updatingStudyTopic(): void
    {
        $this->resetPage();
    }

    public function updatingProposedCategory(): void
    {
        $this->resetPage();
    }

    public function updatingMissingTranslation(): void
    {
        $this->resetPage();
    }

    public function updatingClassificationState(): void
    {
        $this->resetPage();
    }

    public function toggleSelect(int $id): void
    {
        if (in_array($id, $this->selectedIds, true)) {
            $this->selectedIds = array_values(array_filter(
                $this->selectedIds,
                static fn (int $selected): bool => $selected !== $id,
            ));

            return;
        }

        $this->selectedIds[] = $id;
    }

    public function selectPageResults(): void
    {
        $ids = $this->filteredQuery()
            ->orderBy('question_import_batch_id')
            ->orderBy('source_group')
            ->orderBy('original_question_number')
            ->forPage($this->getPage(), 25)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $this->selectedIds = array_values(array_unique([...$this->selectedIds, ...$ids]));
    }

    public function clearSelection(): void
    {
        $this->selectedIds = [];
        $this->confirmBulkAction = false;
    }

    public function bulkMarkReview(EditorialReviewService $editorial): void
    {
        if (! $this->guardBulk()) {
            return;
        }

        $stats = $editorial->bulkMarkForAdditionalReview($this->admin(), $this->selectedIds, $this->bulkNotes ?: null);
        $this->statusMessage = __('admin.bulk_mark_review_result', ['updated' => $stats['updated']]);
        $this->afterBulk();
    }

    public function bulkAssignTopics(EditorialReviewService $editorial): void
    {
        if (! $this->guardBulk()) {
            return;
        }

        if ($this->bulkTopic === '') {
            $this->addError('bulkTopic', __('admin.bulk_topic_required'));

            return;
        }

        $stats = $editorial->bulkAssignStudyTopics(
            $this->admin(),
            $this->selectedIds,
            [$this->bulkTopic],
            approve: true,
            notes: $this->bulkNotes ?: null,
        );

        $this->statusMessage = __('admin.bulk_topics_result', ['updated' => $stats['updated']]);
        $this->afterBulk();
    }

    public function bulkAppendNotes(EditorialReviewService $editorial): void
    {
        if (! $this->guardBulk()) {
            return;
        }

        if (trim($this->bulkNotes) === '') {
            $this->addError('bulkNotes', __('admin.bulk_notes_required'));

            return;
        }

        $stats = $editorial->bulkAppendEditorialNotes($this->admin(), $this->selectedIds, $this->bulkNotes);
        $this->statusMessage = __('admin.bulk_notes_result', ['updated' => $stats['updated']]);
        $this->afterBulk();
    }

    public function exportSelectedForTranslation(StagedTranslationExportService $exportService): void
    {
        $this->authorize('viewAny', QuestionImportEntry::class);

        if ($this->selectedIds === []) {
            $ids = $this->filteredQuery()
                ->orderBy('id')
                ->limit(max(1, min(100, $this->exportLimit)))
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();
        } else {
            $ids = array_slice($this->selectedIds, 0, max(1, min(100, $this->exportLimit)));
        }

        if ($ids === []) {
            $this->addError('selectedIds', __('admin.bulk_selection_required'));

            return;
        }

        $payload = $exportService->export(
            locales: [$this->exportLocale],
            sourceGroups: null,
            pendingOnly: false,
            entryIds: $ids,
            examCategories: $this->editorialCategory !== '' ? [$this->editorialCategory] : null,
            studyTopics: $this->studyTopic !== '' ? [$this->studyTopic] : null,
            limit: max(1, min(100, $this->exportLimit)),
        );

        $path = storage_path('app/menkyo-exports/translations-filtered-'.now()->format('Ymd-His').'.json');
        $exportService->writeJson($path, $payload);

        $this->statusMessage = __('admin.export_ready', [
            'count' => $payload['entry_count'],
            'path' => $path,
        ]);
    }

    public function render(): View
    {
        $entries = $this->filteredQuery()
            ->with([
                'batch:id,source_key,source_filename',
                'translations:id,question_import_entry_id,locale,status,question_text',
            ])
            ->orderBy('question_import_batch_id')
            ->orderBy('source_group')
            ->orderBy('original_question_number')
            ->paginate(25);

        $groups = QuestionImportEntry::query()
            ->select('source_group')
            ->distinct()
            ->orderBy('source_group')
            ->pluck('source_group');

        $families = QuestionImportEntry::query()
            ->select('exam_family')
            ->distinct()
            ->orderBy('exam_family')
            ->pluck('exam_family');

        return view('livewire.admin.question-bank-index', [
            'entries' => $entries,
            'batches' => QuestionImportBatch::query()->orderBy('id')->get(['id', 'source_key']),
            'groups' => $groups,
            'families' => $families,
            'categories' => EditorialExamCategory::cases(),
            'studyTopics' => EditorialStudyTopic::cases(),
            'workflows' => EditorialWorkflowStatus::cases(),
            'locales' => SupportedLocale::codes(),
        ]);
    }

    /**
     * @return Builder<QuestionImportEntry>
     */
    private function filteredQuery(): Builder
    {
        return QuestionImportEntry::query()
            ->when($this->search !== '', function (Builder $query): void {
                $term = '%'.$this->search.'%';
                $query->where(function (Builder $inner) use ($term): void {
                    $inner->where('english_question', 'like', $term)
                        ->orWhere('english_explanation', 'like', $term)
                        ->orWhere('source_group', 'like', $term)
                        ->orWhere('sign_key', 'like', $term);
                });
            })
            ->when($this->sourceKey !== '', function (Builder $query): void {
                $query->whereHas('batch', function (Builder $batch): void {
                    $batch->where('source_key', $this->sourceKey);
                });
            })
            ->when($this->sourceGroup !== '', fn (Builder $query) => $query->where('source_group', $this->sourceGroup))
            ->when($this->examFamily !== '', fn (Builder $query) => $query->where('exam_family', $this->examFamily))
            ->when($this->workflow !== '', fn (Builder $query) => $query->where('editorial_workflow_status', $this->workflow))
            ->when($this->editorialCategory !== '', function (Builder $query): void {
                $query->whereJsonContains('editorial_categories', $this->editorialCategory);
            })
            ->when($this->studyTopic !== '', function (Builder $query): void {
                $query->whereJsonContains('study_topics', $this->studyTopic);
            })
            ->when($this->proposedCategory !== '', fn (Builder $query) => $query->where('proposed_exam_category', $this->proposedCategory))
            ->when($this->classificationState === 'approved', fn (Builder $query) => $query->where('classification_approved', true))
            ->when($this->classificationState === 'pending', fn (Builder $query) => $query->where('classification_approved', false))
            ->when($this->classificationState === 'unclassified_exam', function (Builder $query): void {
                $query->where(function (Builder $inner): void {
                    $inner->where('proposed_exam_category', EditorialExamCategory::Unclassified->value)
                        ->orWhere(function (Builder $family): void {
                            $family->where('exam_family', 'unspecified')
                                ->where(function (Builder $cats): void {
                                    $cats->whereNull('editorial_categories')
                                        ->orWhereJsonContains('editorial_categories', EditorialExamCategory::Unclassified->value)
                                        ->orWhereJsonLength('editorial_categories', 0);
                                });
                        });
                });
            })
            ->when($this->missingTranslation !== '', function (Builder $query): void {
                $locale = $this->missingTranslation;
                $query->whereDoesntHave('translations', function (Builder $translation) use ($locale): void {
                    $translation->where('locale', $locale)
                        ->whereNotNull('question_text')
                        ->where('question_text', '!=', '');
                });
            });
    }

    private function guardBulk(): bool
    {
        $this->authorize('classify', new QuestionImportEntry);

        if ($this->selectedIds === []) {
            $this->addError('selectedIds', __('admin.bulk_selection_required'));

            return false;
        }

        if (! $this->confirmBulkAction) {
            $this->addError('confirmBulkAction', __('admin.bulk_confirm_required'));

            return false;
        }

        return true;
    }

    private function afterBulk(): void
    {
        $this->selectedIds = [];
        $this->confirmBulkAction = false;
        $this->bulkNotes = '';
    }

    private function admin(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->isAdministrator(), 403);

        return $user;
    }
}
