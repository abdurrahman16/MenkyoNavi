<?php

namespace App\Services\Translations;

use App\Enums\ContentReviewStatus;
use App\Enums\CopyrightReuseStatus;
use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Enums\SourceType;
use App\Enums\StagedTranslationStatus;
use App\Enums\TranslationReviewStatus;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\QuestionImportEntry;
use App\Models\QuestionTranslation;
use App\Models\SourceReference;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * @phpstan-type PromotionResult array{
 *     promoted: bool,
 *     question_id: ?int,
 *     blockers: list<string>,
 *     created: bool,
 *     message: string
 * }
 */
class StagedQuestionPromotionService
{
    /**
     * Promote a fully prepared staging entry into a draft Question.
     *
     * Does not publish. Requires approved Japanese text and clears publication blockers
     * unless $allowDraftDespiteBlockers is true for non-publish draft scaffolding.
     *
     * @return PromotionResult
     */
    public function promoteToDraft(
        QuestionImportEntry $entry,
        bool $allowDraftDespiteBlockers = false,
    ): array {
        $entry->loadMissing('translations', 'batch');

        $blockers = $entry->computePublicationBlockers();

        // Draft promotion still requires Japanese + English records and must never publish.
        $hardBlockers = array_values(array_filter(
            $blockers,
            fn (string $blocker): bool => in_array($blocker, [
                'missing_japanese_translation',
                'missing_english_translation_record',
            ], true),
        ));

        if ($hardBlockers !== []) {
            return [
                'promoted' => false,
                'question_id' => $entry->promoted_question_id,
                'blockers' => $hardBlockers,
                'created' => false,
                'message' => 'Promotion blocked: required translations are incomplete.',
            ];
        }

        if (! $allowDraftDespiteBlockers && $blockers !== []) {
            $entry->publication_blockers = $blockers;
            $entry->save();

            return [
                'promoted' => false,
                'question_id' => $entry->promoted_question_id,
                'blockers' => $blockers,
                'created' => false,
                'message' => 'Publication gates are not satisfied; draft promotion refused.',
            ];
        }

        return DB::transaction(function () use ($entry, $blockers, $allowDraftDespiteBlockers): array {
            $japanese = $entry->translationFor('ja');
            $english = $entry->translationFor('en');

            if ($japanese === null || $english === null) {
                throw new RuntimeException('Required translations disappeared during promotion.');
            }

            $sourceReference = $this->resolveSourceReference($entry);

            $attributes = [
                'type' => QuestionType::StandardTrueFalse,
                'status' => QuestionStatus::Draft,
                'difficulty' => 1,
                'japanese_text' => $japanese->question_text,
                'japanese_reading_data' => $japanese->japanese_reading_data,
                'correct_answer' => $entry->correct_answer,
                'explanation_ja' => $japanese->explanation,
                'source_reference_id' => $sourceReference->id,
                'source_page_number' => $entry->stableSourceId(),
                'content_review_status' => ContentReviewStatus::Pending,
                'reviewed_at' => null,
                'is_demo' => false,
                'points' => 1,
            ];

            $created = false;

            if ($entry->promoted_question_id !== null) {
                $question = Question::query()->find($entry->promoted_question_id);

                if ($question === null) {
                    $question = Question::query()->create($attributes);
                    $created = true;
                } else {
                    if ($question->is_demo || $question->status === QuestionStatus::Demo) {
                        throw new RuntimeException('Refusing to mutate an existing demo question during promotion.');
                    }

                    if ($question->status === QuestionStatus::Published) {
                        throw new RuntimeException('Refusing to mutate a published question during promotion.');
                    }

                    $question->fill($attributes);
                    $question->save();
                }
            } else {
                $question = Question::query()->create($attributes);
                $created = true;
            }

            $this->upsertTranslation(
                $question,
                'ja',
                $japanese->question_text ?? '',
                $japanese->explanation,
                $japanese->nuance_note,
                $japanese->status,
            );

            $this->upsertTranslation(
                $question,
                'en',
                $entry->english_question,
                $entry->english_explanation,
                'Exact original English source preserved.',
                StagedTranslationStatus::Draft,
            );

            foreach ($entry->translations as $translation) {
                if (in_array($translation->locale, ['ja', 'en'], true)) {
                    continue;
                }

                if (! $translation->hasCompleteText()) {
                    continue;
                }

                $this->upsertTranslation(
                    $question,
                    $translation->locale,
                    $translation->question_text ?? '',
                    $translation->explanation,
                    $translation->nuance_note,
                    $translation->status,
                );
            }

            $examSlug = $entry->exam_family === 'honmen' ? 'honmen' : 'karimen';
            $examType = ExamType::query()->where('slug', $examSlug)->first();

            if ($examType !== null) {
                $question->examTypes()->syncWithoutDetaching([$examType->id]);
            }

            $entry->promoted_question_id = $question->id;
            $entry->promoted_at = now();
            $entry->publication_blockers = $allowDraftDespiteBlockers ? $blockers : [];
            $entry->is_published = false;
            $entry->save();

            return [
                'promoted' => true,
                'question_id' => $question->id,
                'blockers' => $allowDraftDespiteBlockers ? $blockers : [],
                'created' => $created,
                'message' => $created
                    ? 'Promoted to draft question (unpublished).'
                    : 'Updated existing draft promotion (unpublished).',
            ];
        });
    }

    private function resolveSourceReference(QuestionImportEntry $entry): SourceReference
    {
        $batch = $entry->batch;

        return SourceReference::query()->updateOrCreate(
            [
                'title' => 'Staged English exam trainer import ('.$batch?->source_key.')',
            ],
            [
                'issuing_organization' => 'External HTML study material (rights review required)',
                'source_type' => SourceType::Other,
                'original_url' => null,
                'edition_or_publication_date' => null,
                'page_number' => $entry->stableSourceId(),
                'last_verified_at' => null,
                'copyright_reuse_status' => CopyrightReuseStatus::Unknown,
                'notes' => 'Imported for staging/translation only. Public reuse rights are unclear until reviewed. '
                    .'File checksum: '.($batch?->file_checksum ?? 'unknown'),
            ],
        );
    }

    private function upsertTranslation(
        Question $question,
        string $locale,
        string $questionText,
        ?string $explanation,
        ?string $nuanceNote,
        StagedTranslationStatus $stagedStatus,
    ): void {
        $reviewStatus = match (true) {
            $stagedStatus === StagedTranslationStatus::ReadyForPublication,
            $stagedStatus === StagedTranslationStatus::Reviewed => TranslationReviewStatus::Reviewed,
            $stagedStatus === StagedTranslationStatus::NeedsReview => TranslationReviewStatus::NeedsRevision,
            default => TranslationReviewStatus::Pending,
        };

        QuestionTranslation::query()->updateOrCreate(
            [
                'question_id' => $question->id,
                'locale' => $locale,
            ],
            [
                'question_text' => $questionText,
                'explanation' => $explanation,
                'nuance_note' => $nuanceNote,
                'review_status' => $reviewStatus,
            ],
        );
    }
}
