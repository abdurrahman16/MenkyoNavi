<?php

namespace App\Services\Translations;

use App\Enums\ContentReviewStatus;
use App\Enums\QuestionStatus;
use App\Models\Question;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportReviewAudit;
use App\Models\User;
use App\Services\Editorial\OriginalKarimenPilotAccuracyReviewService;
use App\Services\Editorial\OriginalKarimenPilotSourceRevisionService;
use App\Services\Editorial\OriginalKarimenReleaseGateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Controlled publication of a fully eligible staged entry into the live exam pool.
 *
 * Never bulk-publishes. Never fabricates review decisions.
 *
 * @phpstan-type PublicationResult array{
 *     published: bool,
 *     question_id: ?int,
 *     blockers: list<string>,
 *     message: string
 * }
 */
class StagedQuestionPublicationService
{
    public const AUDIT_ACTION = 'staged_question_published_to_live_pool';

    public function __construct(
        private StagedQuestionPromotionService $promotion,
        private OriginalKarimenPilotAccuracyReviewService $accuracyReviews,
        private OriginalKarimenPilotSourceRevisionService $revisions,
        private OriginalKarimenReleaseGateService $releaseGates,
    ) {}

    /**
     * @return PublicationResult
     */
    public function publishToLivePool(QuestionImportEntry $entry, User $admin): array
    {
        if (! $admin->isAdministrator() || $admin->email_verified_at === null) {
            throw ValidationException::withMessages([
                'publish' => 'Only verified administrators may publish staged questions.',
            ]);
        }

        $entry->loadMissing(['translations', 'batch']);
        $blockers = $this->publicationGateBlockers($entry);

        if ($blockers !== []) {
            $entry->publication_blockers = $entry->computePublicationBlockers();
            $entry->save();

            return [
                'published' => false,
                'question_id' => $entry->promoted_question_id,
                'blockers' => $blockers,
                'message' => 'Publication refused: eligibility gates are not satisfied.',
            ];
        }

        return DB::transaction(function () use ($entry, $admin): array {
            $before = [
                'is_published' => $entry->is_published,
                'promoted_question_id' => $entry->promoted_question_id,
                'question_status' => null,
            ];

            $promoted = $this->promotion->promoteToDraft($entry->fresh(['translations', 'batch']), false);

            if (! $promoted['promoted'] || $promoted['question_id'] === null) {
                return [
                    'published' => false,
                    'question_id' => $entry->promoted_question_id,
                    'blockers' => $promoted['blockers'],
                    'message' => $promoted['message'],
                ];
            }

            $question = Question::query()->lockForUpdate()->findOrFail($promoted['question_id']);

            if ($question->is_demo || $question->status === QuestionStatus::Demo) {
                throw ValidationException::withMessages([
                    'publish' => 'Refusing to publish a demo question.',
                ]);
            }

            $before['question_status'] = $question->status->value;

            $question->forceFill([
                'status' => QuestionStatus::Published,
                'content_review_status' => ContentReviewStatus::Reviewed,
                'reviewed_at' => now(),
                'is_demo' => false,
            ])->save();

            $entry = $entry->fresh(['translations', 'batch']);
            $entry->forceFill([
                'is_published' => true,
                'promoted_question_id' => $question->id,
                'promoted_at' => $entry->promoted_at ?? now(),
                'publication_blockers' => [],
            ])->save();

            QuestionImportReviewAudit::query()->create([
                'question_import_entry_id' => $entry->id,
                'user_id' => $admin->id,
                'action' => self::AUDIT_ACTION,
                'before' => $before,
                'after' => [
                    'is_published' => true,
                    'promoted_question_id' => $question->id,
                    'question_status' => QuestionStatus::Published->value,
                    'content_review_status' => ContentReviewStatus::Reviewed->value,
                ],
                'notes' => 'Authorized publication to live exam pool after eligibility gates passed.',
            ]);

            return [
                'published' => true,
                'question_id' => $question->id,
                'blockers' => [],
                'message' => 'Published to live exam pool.',
            ];
        });
    }

    /**
     * @return list<string>
     */
    public function publicationGateBlockers(QuestionImportEntry $entry): array
    {
        $blockers = $entry->computePublicationBlockers();

        // Staging already-published flag is a blocker for re-promotion; treat as not ready.
        if (in_array($entry->source_group, $this->accuracyReviews->allowedSourceGroups(), true)) {
            if (! $this->accuracyReviews->humanAccuracyAcceptedForCurrentVersion($entry)) {
                $blockers[] = 'human_accuracy_not_accepted_for_current_version';
            }

            if (! $this->releaseGates->editorialApprovedForCurrentVersion($entry->loadMissing('translations'))) {
                $blockers[] = 'human_editorial_approval_missing';
            }

            if ($this->revisions->hasProposal($entry) && ! $this->revisions->isSourceClarificationResolved($entry)) {
                $blockers[] = 'unresolved_source_revision';
            }
        }

        return array_values(array_unique($blockers));
    }
}
