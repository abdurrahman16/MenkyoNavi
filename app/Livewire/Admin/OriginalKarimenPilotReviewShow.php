<?php

namespace App\Livewire\Admin;

use App\Enums\HumanAccuracyReviewDecision;
use App\Enums\SourceRevisionDecision;
use App\Models\QuestionImportEntry;
use App\Services\Editorial\OriginalKarimenPilotAccuracyAudit;
use App\Services\Editorial\OriginalKarimenPilotAccuracyReviewService;
use App\Services\Editorial\OriginalKarimenPilotSourceRevisionService;
use App\Services\Editorial\OriginalKarimenPilotTranslationDraftService;
use App\Services\Editorial\OriginalKarimenReleaseGateService;
use App\Services\Translations\StagedQuestionPublicationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class OriginalKarimenPilotReviewShow extends Component
{
    public QuestionImportEntry $entry;

    public ?string $decision = null;

    public string $notes = '';

    public string $expectedContentFingerprint = '';

    public bool $confirmAcceptance = false;

    public bool $acknowledgesScopeNote = false;

    public bool $acknowledgesProposedStem = false;

    public ?string $revisionDecision = null;

    public string $revisionNotes = '';

    public bool $confirmRevisionApply = false;

    public string $statusMessage = '';

    public bool $editorialApproved = false;

    public string $editorialNotes = '';

    public bool $confirmEditorialApproval = false;

    public bool $answerKeyVerified = true;

    public string $answerKeyNotes = '';

    public string $answerKeyReference = '';

    public bool $rightsCleared = true;

    public string $rightsNotes = '';

    public string $rightsEvidence = '';

    public string $japaneseReadyNotes = '';

    public bool $confirmJapaneseReady = false;

    public function mount(QuestionImportEntry $entry, OriginalKarimenPilotAccuracyReviewService $reviews): void
    {
        $this->authorize('view', $entry);

        try {
            $reviews->assertPilotEntry($entry);
        } catch (ValidationException) {
            abort(404);
        }

        $this->entry = $reviews->refreshStaleAccuracyApproval($entry->load(['translations', 'batch', 'accuracyReviewer']));
        $this->expectedContentFingerprint = $reviews->reviewContentFingerprint($this->entry);
        $this->answerKeyReference = (string) ($this->entry->answer_key_reference ?? '');
        $this->rightsEvidence = (string) ($this->entry->content_rights_evidence ?? '');
    }

    public function submitDecision(OriginalKarimenPilotAccuracyReviewService $reviews): void
    {
        $this->authorize('reviewAccuracy', $this->entry);
        $this->statusMessage = '';

        $this->validate([
            'decision' => ['required', Rule::enum(HumanAccuracyReviewDecision::class)],
            'notes' => ['required', 'string', 'min:12', 'max:5000'],
            'expectedContentFingerprint' => ['required', 'string', 'size:64'],
            'confirmAcceptance' => ['boolean'],
            'acknowledgesScopeNote' => ['boolean'],
            'acknowledgesProposedStem' => ['boolean'],
        ], [], [
            'decision' => '審査判定',
            'notes' => '審査メモ',
        ]);

        $user = Auth::user();
        abort_unless($user !== null && $user->isAdministrator(), 403);

        /** @var HumanAccuracyReviewDecision $decision */
        $decision = HumanAccuracyReviewDecision::from((string) $this->decision);

        try {
            $this->entry = $reviews->submitDecision($this->entry, $user, [
                'decision' => $decision,
                'notes' => $this->notes,
                'expected_content_fingerprint' => $this->expectedContentFingerprint,
                'confirm_acceptance' => $this->confirmAcceptance,
                'acknowledges_scope_note' => $this->acknowledgesScopeNote,
                'acknowledges_proposed_stem' => $this->acknowledgesProposedStem,
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        }

        $this->decision = null;
        $this->notes = '';
        $this->confirmAcceptance = false;
        $this->acknowledgesScopeNote = false;
        $this->acknowledgesProposedStem = false;
        $this->expectedContentFingerprint = $reviews->reviewContentFingerprint($this->entry);
        $this->statusMessage = __('admin.pilot_accuracy_decision_saved', [
            'decision' => $decision->labelJa(),
        ]);
    }

    public function submitRevisionDecision(
        OriginalKarimenPilotSourceRevisionService $revisions,
        OriginalKarimenPilotAccuracyReviewService $reviews,
    ): void {
        $this->authorize('reviewAccuracy', $this->entry);
        $this->statusMessage = '';

        $this->validate([
            'revisionDecision' => ['required', Rule::enum(SourceRevisionDecision::class)],
            'revisionNotes' => ['required', 'string', 'min:12', 'max:5000'],
            'expectedContentFingerprint' => ['required', 'string', 'size:64'],
            'confirmRevisionApply' => ['boolean'],
        ], [], [
            'revisionDecision' => '改訂判定',
            'revisionNotes' => '改訂メモ',
        ]);

        $user = Auth::user();
        abort_unless($user !== null && $user->isAdministrator(), 403);

        /** @var SourceRevisionDecision $decision */
        $decision = SourceRevisionDecision::from((string) $this->revisionDecision);

        try {
            $this->entry = $revisions->submitRevisionDecision($this->entry, $user, [
                'decision' => $decision,
                'notes' => $this->revisionNotes,
                'expected_content_fingerprint' => $this->expectedContentFingerprint,
                'confirm_revision_apply' => $this->confirmRevisionApply,
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        }

        $this->revisionDecision = null;
        $this->revisionNotes = '';
        $this->confirmRevisionApply = false;
        $this->expectedContentFingerprint = $reviews->reviewContentFingerprint($this->entry);
        $this->statusMessage = __('admin.pilot_revision_decision_saved', [
            'decision' => $decision->labelJa(),
        ]);
    }

    public function submitEditorialDecision(
        OriginalKarimenReleaseGateService $gates,
        OriginalKarimenPilotAccuracyReviewService $reviews,
    ): void {
        $this->authorize('reviewAccuracy', $this->entry);
        $this->statusMessage = '';

        $this->validate([
            'editorialNotes' => ['required', 'string', 'min:12', 'max:5000'],
            'expectedContentFingerprint' => ['required', 'string', 'size:64'],
            'editorialApproved' => ['boolean'],
            'confirmEditorialApproval' => ['boolean'],
        ]);

        $user = Auth::user();
        abort_unless($user !== null && $user->isAdministrator(), 403);

        $this->entry = $gates->submitEditorialDecision($this->entry, $user, [
            'approved' => $this->editorialApproved,
            'notes' => $this->editorialNotes,
            'expected_content_fingerprint' => $this->expectedContentFingerprint,
            'confirm_approval' => $this->confirmEditorialApproval,
        ]);

        $this->editorialNotes = '';
        $this->confirmEditorialApproval = false;
        $this->expectedContentFingerprint = $reviews->reviewContentFingerprint($this->entry);
        $this->statusMessage = $this->editorialApproved
            ? __('admin.pilot_editorial_approved_saved')
            : __('admin.pilot_editorial_withheld_saved');
    }

    public function submitAnswerKeyDecision(
        OriginalKarimenReleaseGateService $gates,
        OriginalKarimenPilotAccuracyReviewService $reviews,
    ): void {
        $this->authorize('reviewAccuracy', $this->entry);
        $this->statusMessage = '';

        $this->validate([
            'answerKeyNotes' => ['required', 'string', 'min:12', 'max:5000'],
            'answerKeyReference' => ['nullable', 'string', 'max:2000'],
            'expectedContentFingerprint' => ['required', 'string', 'size:64'],
            'answerKeyVerified' => ['boolean'],
        ]);

        $user = Auth::user();
        abort_unless($user !== null && $user->isAdministrator(), 403);

        $this->entry = $gates->submitAnswerKeyDecision($this->entry, $user, [
            'verified' => $this->answerKeyVerified,
            'notes' => $this->answerKeyNotes,
            'reference' => $this->answerKeyReference,
            'expected_content_fingerprint' => $this->expectedContentFingerprint,
        ]);

        $this->answerKeyNotes = '';
        $this->answerKeyReference = (string) ($this->entry->answer_key_reference ?? '');
        $this->expectedContentFingerprint = $reviews->reviewContentFingerprint($this->entry);
        $this->statusMessage = __('admin.pilot_answer_key_decision_saved');
    }

    public function submitRightsDecision(
        OriginalKarimenReleaseGateService $gates,
        OriginalKarimenPilotAccuracyReviewService $reviews,
    ): void {
        $this->authorize('reviewAccuracy', $this->entry);
        $this->statusMessage = '';

        $this->validate([
            'rightsNotes' => ['required', 'string', 'min:12', 'max:5000'],
            'rightsEvidence' => ['nullable', 'string', 'max:5000'],
            'expectedContentFingerprint' => ['required', 'string', 'size:64'],
            'rightsCleared' => ['boolean'],
        ]);

        $user = Auth::user();
        abort_unless($user !== null && $user->isAdministrator(), 403);

        $this->entry = $gates->submitRightsDecision($this->entry, $user, [
            'cleared' => $this->rightsCleared,
            'notes' => $this->rightsNotes,
            'evidence' => $this->rightsEvidence,
            'expected_content_fingerprint' => $this->expectedContentFingerprint,
        ]);

        $this->rightsNotes = '';
        $this->rightsEvidence = (string) ($this->entry->content_rights_evidence ?? '');
        $this->expectedContentFingerprint = $reviews->reviewContentFingerprint($this->entry);
        $this->statusMessage = __('admin.pilot_rights_decision_saved');
    }

    public function markJapaneseReady(
        OriginalKarimenReleaseGateService $gates,
        OriginalKarimenPilotAccuracyReviewService $reviews,
    ): void {
        $this->authorize('reviewAccuracy', $this->entry);
        $this->statusMessage = '';

        $this->validate([
            'japaneseReadyNotes' => ['required', 'string', 'min:12', 'max:5000'],
            'expectedContentFingerprint' => ['required', 'string', 'size:64'],
            'confirmJapaneseReady' => ['boolean'],
        ]);

        $user = Auth::user();
        abort_unless($user !== null && $user->isAdministrator(), 403);

        $this->entry = $gates->markJapaneseReadyForPublication($this->entry, $user, [
            'notes' => $this->japaneseReadyNotes,
            'expected_content_fingerprint' => $this->expectedContentFingerprint,
            'confirm_ready' => $this->confirmJapaneseReady,
        ]);

        $this->japaneseReadyNotes = '';
        $this->confirmJapaneseReady = false;
        $this->expectedContentFingerprint = $reviews->reviewContentFingerprint($this->entry);
        $this->statusMessage = __('admin.pilot_japanese_ready_saved');
    }

    public function publishEligibleCandidate(
        StagedQuestionPublicationService $publication,
        OriginalKarimenPilotAccuracyReviewService $reviews,
    ): void {
        $this->authorize('updateReview', $this->entry);
        $this->statusMessage = '';

        $user = Auth::user();
        abort_unless($user !== null && $user->isAdministrator() && $user->email_verified_at !== null, 403);

        $result = $publication->publishToLivePool($this->entry->fresh(['translations', 'batch']), $user);
        $this->entry = $reviews->refreshStaleAccuracyApproval(
            $this->entry->fresh(['translations', 'batch', 'accuracyReviewer'])
        );
        $this->expectedContentFingerprint = $reviews->reviewContentFingerprint($this->entry);

        if ($result['published']) {
            $this->statusMessage = __('admin.publish_to_live_pool_success', [
                'id' => (string) $result['question_id'],
            ]);

            return;
        }

        $this->statusMessage = __('admin.publish_to_live_pool_blocked', [
            'blockers' => implode(', ', $result['blockers']),
        ]);
    }

    public function render(
        OriginalKarimenPilotAccuracyReviewService $reviews,
        OriginalKarimenPilotAccuracyAudit $audit,
        OriginalKarimenPilotSourceRevisionService $revisions,
        OriginalKarimenPilotTranslationDraftService $translations,
        OriginalKarimenReleaseGateService $gates,
        StagedQuestionPublicationService $publication,
    ): View {
        $this->entry->loadMissing(['translations', 'batch', 'accuracyReviewer']);
        $number = (int) $this->entry->original_question_number;
        $isPilot = $this->entry->source_group === OriginalKarimenPilotAccuracyReviewService::SOURCE_GROUP;
        $auditRow = $isPilot ? $audit->forNumber($number) : null;
        $proposal = $revisions->proposalForEntry($this->entry);
        $hasProposal = $revisions->hasProposal($this->entry);

        $orderedIds = $reviews->originalEntriesQuery()
            ->orderBy('original_question_number')
            ->orderBy('id')
            ->pluck('id')
            ->values();
        $currentIndex = $orderedIds->search($this->entry->id);
        $previousId = $currentIndex === false || $currentIndex < 1 ? null : $orderedIds[$currentIndex - 1];
        $nextId = $currentIndex === false || $currentIndex >= ($orderedIds->count() - 1)
            ? null
            : $orderedIds[$currentIndex + 1];

        return view('livewire.admin.original-karimen-pilot-review-show', [
            'reviews' => $reviews,
            'history' => $reviews->reviewHistory($this->entry),
            'revisionHistory' => $revisions->revisionHistory($this->entry),
            'auditRow' => $auditRow,
            'proposal' => $proposal,
            'hasSourceProposal' => $hasProposal,
            'sourceIssueResolved' => $hasProposal ? $revisions->isSourceClarificationResolved($this->entry) : true,
            'translationEligibility' => $hasProposal
                ? $revisions->translationEligibilityLabel($this->entry)
                : ($isPilot && in_array($number, $translations->eligibleCandidateNumbers(), true)
                    ? 'eligible_for_drafting'
                    : 'not_in_draft_set'),
            'fingerprint' => $reviews->reviewContentFingerprint($this->entry),
            'acceptedCurrent' => $reviews->humanAccuracyAcceptedForCurrentVersion($this->entry),
            'decisions' => HumanAccuracyReviewDecision::cases(),
            'revisionDecisions' => SourceRevisionDecision::cases(),
            'japanese' => $this->entry->translationFor('ja'),
            'previousEntryId' => $previousId,
            'nextEntryId' => $nextId,
            'reviewPosition' => $currentIndex === false ? null : ($currentIndex + 1),
            'reviewTotal' => $orderedIds->count(),
            'editorialApprovedCurrent' => $gates->editorialApprovedForCurrentVersion($this->entry),
            'publicationBlockers' => $publication->publicationGateBlockers($this->entry),
        ])->title('Original Karimen accuracy review #'.$this->entry->original_question_number);
    }
}
