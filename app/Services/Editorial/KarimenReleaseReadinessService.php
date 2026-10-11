<?php

namespace App\Services\Editorial;

use App\Enums\AnswerKeyReviewStatus;
use App\Enums\ContentRightsReviewStatus;
use App\Enums\EditorialWorkflowStatus;
use App\Enums\ExamAttemptType;
use App\Enums\QuestionStatus;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\QuestionImportEntry;
use App\Services\Exams\OriginalKarimenAdminPreviewService;
use App\Services\Exams\QuestionSelectionService;
use Illuminate\Support\Collection;

/**
 * Read-only release readiness report for the original Japanese Karimen 50.
 */
class KarimenReleaseReadinessService
{
    public function __construct(
        private OriginalKarimenPilotAccuracyReviewService $reviews,
        private OriginalKarimenPilotSourceRevisionService $revisions,
        private OriginalKarimenReleaseGateService $releaseGates,
        private OriginalKarimenAdminPreviewService $preview,
        private QuestionSelectionService $selection,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        /** @var Collection<int, QuestionImportEntry> $entries */
        $entries = $this->reviews->originalEntriesQuery()
            ->with(['translations', 'batch'])
            ->orderBy('original_question_number')
            ->get();

        $preview = $this->preview->readiness();
        $sourceSupported = [];
        $clarificationPending = [];
        $humanAccepted = [];
        $answerKeyVerified = [];
        $editorialApproved = [];
        $rightsCleared = [];
        $published = [];
        $unresolvedRevision = [];
        $blocking = [
            'missing_candidates' => $preview['missing_ids'],
            'incomplete_candidates' => $preview['incomplete_ids'],
            'human_accuracy_pending' => [],
            'answer_key_unverified' => [],
            'editorial_unapproved' => [],
            'rights_uncleared' => [],
            'unresolved_source_revision' => [],
            'unpublished' => [],
            'publication_blockers' => [],
        ];

        foreach ($entries as $entry) {
            $id = (string) data_get($entry->source_metadata, 'candidate_id', $entry->stableSourceId());
            $assessment = (string) data_get($entry->source_metadata, 'research_assessment', '');

            if ($assessment === 'supported_as_written') {
                $sourceSupported[] = $id;
            }

            if ($assessment === 'supported_with_clarification' || $this->preview->isClarificationCandidate($entry)) {
                $clarificationPending[] = $id;
            }

            if ($this->reviews->humanAccuracyAcceptedForCurrentVersion($entry)) {
                $humanAccepted[] = $id;
            } else {
                $blocking['human_accuracy_pending'][] = $id;
            }

            if ($entry->answer_key_status === AnswerKeyReviewStatus::Verified) {
                $answerKeyVerified[] = $id;
            } else {
                $blocking['answer_key_unverified'][] = $id;
            }

            if ($this->releaseGates->editorialApprovedForCurrentVersion($entry)) {
                $editorialApproved[] = $id;
            } else {
                $blocking['editorial_unapproved'][] = $id;
            }

            if ($entry->content_rights_status === ContentRightsReviewStatus::ApprovedForPublicReuse) {
                $rightsCleared[] = $id;
            } else {
                $blocking['rights_uncleared'][] = $id;
            }

            if ($this->revisions->hasProposal($entry) && ! $this->revisions->isSourceClarificationResolved($entry)) {
                $unresolvedRevision[] = $id;
                $blocking['unresolved_source_revision'][] = $id;
            }

            if ($entry->is_published) {
                $published[] = $id;
            } else {
                $blocking['unpublished'][] = $id;
            }

            $blockers = $entry->computePublicationBlockers();
            if ($blockers !== []) {
                $blocking['publication_blockers'][] = [
                    'id' => $id,
                    'blockers' => $blockers,
                ];
            }
        }

        $karimen = ExamType::query()->where('slug', 'karimen')->where('is_active', true)->first();
        $honmen = ExamType::query()->where('slug', 'honmen')->where('is_active', true)->first();
        $gaimen = ExamType::query()->where('slug', 'gaimen-kirikae')->where('is_active', true)->first();

        $jaKarimen = $karimen !== null
            ? $this->selection->fullAvailability($karimen, 'ja')
            : null;
        $localeAvailability = [];

        foreach (['ja', 'en', 'zh', 'vi', 'fil', 'ne', 'bn', 'ko'] as $locale) {
            if ($karimen === null) {
                $localeAvailability[$locale] = ['can_start' => false, 'available_standard' => 0, 'message' => 'Karimen exam type missing'];

                continue;
            }

            $availability = $this->selection->fullAvailability($karimen, $locale);
            $localeAvailability[$locale] = [
                'can_start' => $availability->canStart,
                'available_standard' => $availability->availableStandard,
                'available_illustrated' => $availability->availableIllustrated,
                'required_standard' => $availability->requiredStandard,
                'required_illustrated' => $availability->requiredIllustrated,
                'message' => $availability->message,
            ];
        }

        $livePoolEligible = Question::query()
            ->where('is_demo', false)
            ->where('status', QuestionStatus::Published)
            ->whereHas('examTypes', fn ($q) => $q->where('slug', 'karimen'))
            ->count();

        $honmenAvailability = $honmen !== null ? $this->selection->fullAvailability($honmen, 'ja') : null;
        $gaimenAvailability = $gaimen !== null ? $this->selection->fullAvailability($gaimen, 'ja') : null;

        $publicReady = $jaKarimen?->canStart === true
            && count($humanAccepted) >= OriginalKarimenAdminPreviewService::REQUIRED_COUNT
            && count($published) >= OriginalKarimenAdminPreviewService::REQUIRED_COUNT
            && $unresolvedRevision === []
            && $preview['ready'];

        return [
            'read_only' => true,
            'mutates_records' => false,
            'total_original_japanese_candidates' => OriginalKarimenAdminPreviewService::REQUIRED_COUNT,
            'privately_staged_count' => $entries->count(),
            'source_supported_count' => count($sourceSupported),
            'source_supported_ids' => $sourceSupported,
            'clarification_pending_count' => count(array_unique($clarificationPending)),
            'clarification_pending_ids' => array_values(array_unique($clarificationPending)),
            'human_accuracy_accepted_count' => count($humanAccepted),
            'human_accuracy_accepted_ids' => $humanAccepted,
            'answer_key_verified_count' => count($answerKeyVerified),
            'answer_key_verified_ids' => $answerKeyVerified,
            'editorial_approved_count' => count($editorialApproved),
            'editorial_approved_ids' => $editorialApproved,
            'rights_cleared_count' => count($rightsCleared),
            'rights_cleared_ids' => $rightsCleared,
            'published_count' => count($published),
            'published_ids' => $published,
            'unresolved_source_revision_count' => count($unresolvedRevision),
            'unresolved_source_revision_ids' => $unresolvedRevision,
            'japanese_live_pool_eligible_count' => $livePoolEligible,
            'japanese_public_karimen_can_start' => $jaKarimen?->canStart ?? false,
            'japanese_public_karimen_message' => $jaKarimen?->message,
            'locale_availability' => $localeAvailability,
            'honmen' => [
                'can_start' => $honmenAvailability?->canStart ?? false,
                'available_standard' => $honmenAvailability?->availableStandard ?? 0,
                'available_illustrated' => $honmenAvailability?->availableIllustrated ?? 0,
                'required_standard' => $honmenAvailability?->requiredStandard ?? 0,
                'required_illustrated' => $honmenAvailability?->requiredIllustrated ?? 0,
                'message' => $honmenAvailability?->message,
            ],
            'gaimen' => [
                'can_start' => $gaimenAvailability?->canStart ?? false,
                'available_standard' => $gaimenAvailability?->availableStandard ?? 0,
                'available_illustrated' => $gaimenAvailability?->availableIllustrated ?? 0,
                'required_standard' => $gaimenAvailability?->requiredStandard ?? 0,
                'required_illustrated' => $gaimenAvailability?->requiredIllustrated ?? 0,
                'message' => $gaimenAvailability?->message,
            ],
            'blocking' => $blocking,
            'public_release_ready' => $publicReady,
            'admin_preview_attempt_type' => ExamAttemptType::AdminPreview->value,
            'workflow_note' => 'Research-supported is not human-approved. Admin preview does not affect public eligibility.',
            'editorial_workflow_sample' => $entries
                ->take(3)
                ->map(fn (QuestionImportEntry $entry): string => ($entry->editorial_workflow_status instanceof EditorialWorkflowStatus
                    ? $entry->editorial_workflow_status->value
                    : (string) $entry->editorial_workflow_status))
                ->all(),
        ];
    }
}
