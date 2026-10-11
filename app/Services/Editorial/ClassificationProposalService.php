<?php

namespace App\Services\Editorial;

use App\Enums\EditorialExamCategory;
use App\Enums\EditorialStudyTopic;
use App\Enums\EditorialWorkflowStatus;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportReviewAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Generates non-approved exam-category and study-topic proposals from source metadata.
 * Never mutates original English fields, answer keys, or content-rights status.
 */
class ClassificationProposalService
{
    public const SOURCE_GROUP_KARIMEN = 'source_group_karimen_tests';

    public const SOURCE_GROUP_HONMEN = 'source_group_honmen_dl';

    public const SOURCE_FAMILY_UNSPECIFIED = 'source_exam_family_unspecified';

    public const SOURCE_TOPIC_METADATA = 'source_group_topic_keywords';

    /**
     * @return array{karimen: int, honmen: int, unclassified: int, skipped_approved: int, updated: int}
     */
    public function generateExamClassificationProposals(User $admin, bool $overwriteUnapproved = true): array
    {
        $stats = [
            'karimen' => 0,
            'honmen' => 0,
            'unclassified' => 0,
            'skipped_approved' => 0,
            'updated' => 0,
        ];

        QuestionImportEntry::query()
            ->orderBy('id')
            ->chunkById(100, function ($entries) use ($admin, $overwriteUnapproved, &$stats): void {
                foreach ($entries as $entry) {
                    if ($entry->classification_approved) {
                        $stats['skipped_approved']++;

                        continue;
                    }

                    if (! $overwriteUnapproved && filled($entry->proposed_exam_category)) {
                        continue;
                    }

                    $proposed = $this->proposeExamCategoryForEntry($entry);

                    if (
                        $entry->proposed_exam_category === $proposed->value
                        && $entry->classification_proposal_source !== null
                    ) {
                        continue;
                    }

                    $this->writeProposal($entry, $admin, $proposed);

                    $stats[$proposed === EditorialExamCategory::Karimen ? 'karimen'
                        : ($proposed === EditorialExamCategory::Honmen ? 'honmen' : 'unclassified')]++;
                    $stats['updated']++;
                }
            });

        return $stats;
    }

    /**
     * @return array{updated: int, skipped_approved_topics: int}
     */
    public function generateStudyTopicProposals(User $admin, bool $overwriteUnapproved = true): array
    {
        $stats = ['updated' => 0, 'skipped_approved_topics' => 0];

        QuestionImportEntry::query()
            ->where('exam_family', 'unspecified')
            ->orderBy('id')
            ->chunkById(100, function ($entries) use ($admin, $overwriteUnapproved, &$stats): void {
                foreach ($entries as $entry) {
                    if ($entry->study_topics_approved) {
                        $stats['skipped_approved_topics']++;

                        continue;
                    }

                    if (! $overwriteUnapproved && filled($entry->study_topics)) {
                        continue;
                    }

                    $topics = EditorialStudyTopic::proposeFromSourceGroup(
                        $entry->source_group,
                        $entry->source_metadata,
                    );
                    $topicValues = array_map(
                        static fn (EditorialStudyTopic $topic): string => $topic->value,
                        $topics,
                    );

                    if ($entry->study_topics === $topicValues) {
                        continue;
                    }

                    $before = $entry->only(['study_topics', 'study_topics_approved']);

                    DB::transaction(function () use ($entry, $admin, $topicValues, $before): void {
                        $entry->fill([
                            'study_topics' => $topicValues,
                            'study_topics_approved' => false,
                        ])->save();

                        QuestionImportReviewAudit::query()->create([
                            'question_import_entry_id' => $entry->id,
                            'user_id' => $admin->id,
                            'action' => 'study_topics_proposed_from_source',
                            'before' => $before,
                            'after' => $entry->only(['study_topics', 'study_topics_approved']),
                            'notes' => 'Suggested study topics from source_group keywords; not exam eligibility.',
                        ]);
                    });

                    $stats['updated']++;
                }
            });

        return $stats;
    }

    public function proposeExamCategoryForEntry(QuestionImportEntry $entry): EditorialExamCategory
    {
        if (preg_match('/^karimen_test_[1-5]$/', $entry->source_group) === 1) {
            return EditorialExamCategory::Karimen;
        }

        if (preg_match('/^honmen_dl_[1-4]$/', $entry->source_group) === 1) {
            return EditorialExamCategory::Honmen;
        }

        if ($entry->exam_family === 'karimen') {
            return EditorialExamCategory::Karimen;
        }

        if ($entry->exam_family === 'honmen') {
            return EditorialExamCategory::Honmen;
        }

        if ($entry->exam_family === 'gaimen_kirikae') {
            return EditorialExamCategory::GaimenKirikae;
        }

        return EditorialExamCategory::Unclassified;
    }

    public function proposalSourceForEntry(QuestionImportEntry $entry): string
    {
        if (preg_match('/^karimen_test_[1-5]$/', $entry->source_group) === 1) {
            return self::SOURCE_GROUP_KARIMEN;
        }

        if (preg_match('/^honmen_dl_[1-4]$/', $entry->source_group) === 1) {
            return self::SOURCE_GROUP_HONMEN;
        }

        return self::SOURCE_FAMILY_UNSPECIFIED;
    }

    /**
     * @return Builder<QuestionImportEntry>
     */
    public function karimenCandidatesQuery(): Builder
    {
        return QuestionImportEntry::query()->where(function (Builder $query): void {
            $query->where('proposed_exam_category', EditorialExamCategory::Karimen->value)
                ->orWhere(function (Builder $inner): void {
                    $inner->whereNull('proposed_exam_category')
                        ->where(function (Builder $group): void {
                            $group->where('source_group', 'like', 'karimen_test_%')
                                ->orWhere('exam_family', 'karimen');
                        });
                });
        });
    }

    /**
     * @return Builder<QuestionImportEntry>
     */
    public function honmenCandidatesQuery(): Builder
    {
        return QuestionImportEntry::query()->where(function (Builder $query): void {
            $query->where('proposed_exam_category', EditorialExamCategory::Honmen->value)
                ->orWhere(function (Builder $inner): void {
                    $inner->whereNull('proposed_exam_category')
                        ->where(function (Builder $group): void {
                            $group->where('source_group', 'like', 'honmen_dl_%')
                                ->orWhere('exam_family', 'honmen');
                        });
                });
        });
    }

    /**
     * @return array{eligible: int, would_change: int, already_approved: int}
     */
    public function previewBulkApproval(string $category, bool $includeAlreadyApproved = false): array
    {
        $categoryEnum = EditorialExamCategory::from($category);
        $query = $categoryEnum === EditorialExamCategory::Karimen
            ? $this->karimenCandidatesQuery()
            : ($categoryEnum === EditorialExamCategory::Honmen
                ? $this->honmenCandidatesQuery()
                : QuestionImportEntry::query()->where('proposed_exam_category', $category));

        $eligible = (clone $query)->count();
        $alreadyApproved = (clone $query)->where('classification_approved', true)->count();

        $wouldChange = (clone $query)
            ->when(! $includeAlreadyApproved, fn (Builder $q) => $q->where('classification_approved', false))
            ->where(function (Builder $q) use ($category): void {
                $q->where('classification_approved', false)
                    ->orWhereJsonDoesntContain('editorial_categories', $category)
                    ->orWhereNull('editorial_categories');
            })
            ->count();

        return [
            'eligible' => $eligible,
            'would_change' => $wouldChange,
            'already_approved' => $alreadyApproved,
        ];
    }

    private function writeProposal(
        QuestionImportEntry $entry,
        User $admin,
        EditorialExamCategory $proposed,
    ): void {
        $source = $this->proposalSourceForEntry($entry);
        $before = $entry->only([
            'proposed_exam_category',
            'classification_proposal_source',
            'editorial_categories',
            'classification_approved',
            'editorial_workflow_status',
        ]);

        DB::transaction(function () use ($entry, $admin, $proposed, $source, $before): void {
            $attributes = [
                'proposed_exam_category' => $proposed->value,
                'classification_proposal_source' => $source,
            ];

            // Keep second-source exam eligibility unclassified until reviewed.
            if ($proposed === EditorialExamCategory::Unclassified) {
                if (blank($entry->editorial_categories)) {
                    $attributes['editorial_categories'] = [EditorialExamCategory::Unclassified->value];
                    $attributes['classification_approved'] = false;
                    $attributes['editorial_workflow_status'] = EditorialWorkflowStatus::NeedsReview->value;
                }
            } elseif (blank($entry->editorial_categories) || $entry->editorial_categories === [EditorialExamCategory::Unclassified->value]) {
                $attributes['editorial_categories'] = [$proposed->value];
                $attributes['classification_approved'] = false;
                $attributes['editorial_workflow_status'] = EditorialWorkflowStatus::NeedsReview->value;
            }

            $entry->fill($attributes);
            $entry->publication_blockers = $entry->computePublicationBlockers();
            $entry->save();

            QuestionImportReviewAudit::query()->create([
                'question_import_entry_id' => $entry->id,
                'user_id' => $admin->id,
                'action' => 'exam_classification_proposed',
                'before' => $before,
                'after' => $entry->only(array_keys($before)),
                'notes' => 'Practice classification proposal from '.$source.'; not auto-approved.',
            ]);
        });
    }
}
