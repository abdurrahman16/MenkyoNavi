<?php

namespace App\Livewire\Admin;

use App\Enums\EditorialExamCategory;
use App\Models\QuestionImportEntry;
use App\Models\User;
use App\Services\Editorial\ClassificationProposalService;
use App\Services\Editorial\EditorialReviewService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Bulk exam classification')]
class QuestionBankBulkClassification extends Component
{
    use WithPagination;

    #[Url]
    public string $focusCategory = 'karimen';

    public bool $overrideApproved = false;

    public bool $confirmBulk = false;

    public string $statusMessage = '';

    /** @var list<int> */
    public array $selectedIds = [];

    public function mount(): void
    {
        $this->authorize('viewAny', QuestionImportEntry::class);
    }

    public function updatedFocusCategory(): void
    {
        $this->resetPage();
        $this->selectedIds = [];
        $this->confirmBulk = false;
    }

    public function generateProposals(ClassificationProposalService $proposals): void
    {
        $admin = $this->admin();

        $examStats = $proposals->generateExamClassificationProposals($admin);
        $topicStats = $proposals->generateStudyTopicProposals($admin);

        $this->statusMessage = __('admin.bulk_proposals_generated', [
            'exam' => $examStats['updated'],
            'topics' => $topicStats['updated'],
            'skipped' => $examStats['skipped_approved'],
        ]);
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

    public function selectEligiblePage(ClassificationProposalService $proposals): void
    {
        $ids = $this->candidatesQuery($proposals)
            ->when(! $this->overrideApproved, fn (Builder $q) => $q->where('classification_approved', false))
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
        $this->confirmBulk = false;
    }

    public function approveSelected(EditorialReviewService $editorial): void
    {
        $admin = $this->admin();

        if (! $this->confirmBulk) {
            $this->addError('confirmBulk', __('admin.bulk_confirm_required'));

            return;
        }

        if ($this->selectedIds === []) {
            $this->addError('selectedIds', __('admin.bulk_selection_required'));

            return;
        }

        $category = EditorialExamCategory::from($this->focusCategory);

        $stats = $editorial->bulkApproveExamClassifications(
            $admin,
            $this->selectedIds,
            $category,
            $this->overrideApproved,
        );

        $this->statusMessage = __('admin.bulk_approve_result', [
            'updated' => $stats['updated'],
            'skipped_approved' => $stats['skipped_approved'],
            'skipped_missing' => $stats['skipped_missing_proposal'],
        ]);

        $this->selectedIds = [];
        $this->confirmBulk = false;
    }

    public function render(ClassificationProposalService $proposals): View
    {
        $preview = $proposals->previewBulkApproval($this->focusCategory, $this->overrideApproved);

        /** @var LengthAwarePaginator<int, QuestionImportEntry> $entries */
        $entries = $this->candidatesQuery($proposals)
            ->orderBy('source_group')
            ->orderBy('original_question_number')
            ->paginate(25);

        return view('livewire.admin.question-bank-bulk-classification', [
            'entries' => $entries,
            'preview' => $preview,
            'categories' => [
                EditorialExamCategory::Karimen,
                EditorialExamCategory::Honmen,
            ],
        ]);
    }

    /**
     * @return Builder<QuestionImportEntry>
     */
    private function candidatesQuery(ClassificationProposalService $proposals): Builder
    {
        return match ($this->focusCategory) {
            'honmen' => $proposals->honmenCandidatesQuery(),
            default => $proposals->karimenCandidatesQuery(),
        };
    }

    private function admin(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->isAdministrator(), 403);

        $this->authorize('classify', new QuestionImportEntry);

        return $user;
    }
}
