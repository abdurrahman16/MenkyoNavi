<?php

namespace App\Livewire\Admin;

use App\Models\QuestionImportEntry;
use App\Services\Editorial\OriginalKarimenExpansionStagingService;
use App\Services\Editorial\OriginalKarimenPilotAccuracyReviewService;
use App\Services\Editorial\OriginalKarimenPilotStagingService;
use App\Services\Editorial\OriginalKarimenPilotTranslationDraftService;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Original Karimen accuracy review')]
class OriginalKarimenPilotReviewIndex extends Component
{
    #[Url]
    public string $filter = 'pending';

    #[Url]
    public string $collection = 'all';

    public function mount(): void
    {
        $this->authorize('viewAny', QuestionImportEntry::class);

        if (! in_array($this->filter, ['pending', 'needs_clarification', 'accepted', 'revision_requested', 'rejected_deferred', 'all'], true)) {
            $this->filter = 'pending';
        }

        if (! in_array($this->collection, ['all', 'pilot', 'expansion'], true)) {
            $this->collection = 'all';
        }
    }

    public function updatedFilter(string $value): void
    {
        if (! in_array($value, ['pending', 'needs_clarification', 'accepted', 'revision_requested', 'rejected_deferred', 'all'], true)) {
            $this->filter = 'pending';
        }
    }

    public function updatedCollection(string $value): void
    {
        if (! in_array($value, ['all', 'pilot', 'expansion'], true)) {
            $this->collection = 'all';
        }
    }

    public function render(
        OriginalKarimenPilotAccuracyReviewService $reviews,
        OriginalKarimenPilotTranslationDraftService $translationDrafts,
    ): View {
        /** @var Collection<int, QuestionImportEntry> $entries */
        $entries = $reviews->filteredEntries($this->filter);

        if ($this->collection === 'pilot') {
            $entries = $entries->where('source_group', OriginalKarimenPilotStagingService::SOURCE_GROUP)->values();
        } elseif ($this->collection === 'expansion') {
            $entries = $entries->where('source_group', OriginalKarimenExpansionStagingService::SOURCE_GROUP)->values();
        }

        return view('livewire.admin.original-karimen-pilot-review-index', [
            'entries' => $entries,
            'collectionKey' => OriginalKarimenPilotStagingService::COLLECTION_KEY,
            'expansionCollectionKey' => OriginalKarimenExpansionStagingService::COLLECTION_KEY,
            'totalPilot' => $reviews->pilotEntriesQuery()->count(),
            'totalExpansion' => $reviews->originalEntriesQuery()
                ->where('source_group', OriginalKarimenExpansionStagingService::SOURCE_GROUP)
                ->count(),
            'totalOriginal' => $reviews->originalEntriesQuery()->count(),
            'reviews' => $reviews,
            'eligibleTranslationNumbers' => $translationDrafts->eligibleCandidateNumbers(),
            'withheldTranslations' => $translationDrafts->withheldCandidates(),
        ]);
    }
}
