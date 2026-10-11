<?php

namespace App\Livewire\Admin;

use App\Enums\DuplicatePairDecision;
use App\Models\QuestionImportDuplicateReview;
use App\Models\QuestionImportEntry;
use App\Models\User;
use App\Services\Editorial\EditorialReviewService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Similar question review')]
class DuplicatePairReview extends Component
{
    public ?int $selectedReviewId = null;

    public string $decision = '';

    public string $notes = '';

    public string $statusMessage = '';

    public function mount(EditorialReviewService $editorial, ?int $review = null): void
    {
        $this->authorize('viewAny', QuestionImportEntry::class);
        $editorial->ensureSimilarPairReviewsSeeded();

        if ($review !== null) {
            $this->selectReview($review);
        }
    }

    public function selectReview(int $reviewId): void
    {
        $review = QuestionImportDuplicateReview::query()->findOrFail($reviewId);
        $this->authorize('reviewDuplicate', $review->entry);

        $this->selectedReviewId = $review->id;
        $this->decision = $review->decision->value;
        $this->notes = (string) ($review->notes ?? '');
        $this->statusMessage = '';
    }

    public function saveDecision(EditorialReviewService $editorial): void
    {
        $review = QuestionImportDuplicateReview::query()
            ->with(['entry', 'relatedEntry'])
            ->findOrFail($this->selectedReviewId);

        $this->authorize('reviewDuplicate', $review->entry);

        $validated = $this->validate([
            'decision' => ['required', Rule::enum(DuplicatePairDecision::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        $editorial->recordDuplicateDecision(
            $review->entry,
            $review->relatedEntry,
            $user,
            DuplicatePairDecision::from($validated['decision']),
            $review->similarity_reason,
            $validated['notes'] ?: null,
        );

        $this->statusMessage = 'Duplicate-pair decision recorded. Both source records remain preserved.';
        $this->selectReview($review->id);
    }

    public function render(): View
    {
        $reviews = QuestionImportDuplicateReview::query()
            ->with([
                'entry.batch:id,source_key,source_filename',
                'relatedEntry.batch:id,source_key,source_filename',
                'reviewer:id,name,email',
            ])
            ->orderByDesc('id')
            ->get();

        $selected = $this->selectedReviewId === null
            ? null
            : $reviews->firstWhere('id', $this->selectedReviewId);

        return view('livewire.admin.duplicate-pair-review', [
            'reviews' => $reviews,
            'selected' => $selected,
            'decisions' => DuplicatePairDecision::cases(),
        ]);
    }
}
