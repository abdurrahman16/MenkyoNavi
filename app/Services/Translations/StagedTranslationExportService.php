<?php

namespace App\Services\Translations;

use App\Enums\StagedTranslationStatus;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportEntryTranslation;
use App\Support\SupportedLocale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use RuntimeException;

class StagedTranslationExportService
{
    public const FORMAT_VERSION = 1;

    public function __construct(
        private TranslationQualityChecker $qualityChecker,
    ) {}

    /**
     * @param  list<string>|null  $locales
     * @param  list<string>|null  $sourceGroups
     * @param  list<int>|null  $entryIds
     * @param  list<string>|null  $examCategories
     * @param  list<string>|null  $studyTopics
     * @return array{
     *     format_version: int,
     *     exported_at: string,
     *     locales: list<string>,
     *     entry_count: int,
     *     filters: array<string, mixed>,
     *     rows: list<array<string, mixed>>
     * }
     */
    public function export(
        ?array $locales = null,
        ?array $sourceGroups = null,
        bool $pendingOnly = false,
        ?array $entryIds = null,
        ?array $examCategories = null,
        ?array $studyTopics = null,
        ?string $sourceKey = null,
        ?string $workflowStatus = null,
        ?string $contentRightsStatus = null,
        ?string $answerKeyStatus = null,
        ?int $limit = null,
    ): array {
        $locales = $this->normalizeLocales($locales);

        $query = QuestionImportEntry::query()
            ->with(['translations', 'batch:id,source_key'])
            ->orderBy('exam_family')
            ->orderBy('source_group')
            ->orderBy('original_question_number');

        $this->applyFilters($query, $sourceGroups, $entryIds, $examCategories, $studyTopics, $sourceKey, $workflowStatus, $contentRightsStatus, $answerKeyStatus);

        if ($limit !== null) {
            $query->limit(max(1, min(500, $limit)));
        }

        /** @var Collection<int, QuestionImportEntry> $entries */
        $entries = $query->get();

        if ($entries->isEmpty()) {
            throw new RuntimeException('No staged import entries found to export.');
        }

        $rows = [];

        foreach ($entries as $entry) {
            foreach ($locales as $locale) {
                $existing = $entry->translationFor($locale);

                if ($pendingOnly && $existing !== null
                    && $existing->status !== StagedTranslationStatus::PendingTranslation
                    && $existing->hasCompleteText()) {
                    continue;
                }

                $questionText = $existing?->question_text;
                $explanation = $existing?->explanation;
                $figureCaption = $existing?->figure_caption;
                $status = $existing?->status?->value ?? StagedTranslationStatus::PendingTranslation->value;

                if ($locale === 'en' && ($questionText === null || $questionText === '')) {
                    $questionText = $entry->english_question;
                    $explanation = $entry->english_explanation;
                    $figureCaption = $entry->figure_caption;
                    $status = StagedTranslationStatus::Draft->value;
                }

                $qualityFlags = $this->qualityChecker->check($entry, [
                    'locale' => $locale,
                    'question_text' => $questionText,
                    'explanation' => $explanation,
                    'figure_caption' => $figureCaption,
                ]);

                $rows[] = [
                    'source_entry_id' => $entry->id,
                    'source_stable_id' => $entry->stableSourceId(),
                    'source_key' => $entry->batch?->source_key,
                    'source_group' => $entry->source_group,
                    'exam_family' => $entry->exam_family,
                    'proposed_exam_category' => $entry->proposed_exam_category,
                    'editorial_categories' => $entry->editorial_categories,
                    'study_topics' => $entry->study_topics,
                    'original_question_number' => $entry->original_question_number,
                    'source_entry_checksum' => $entry->source_entry_checksum,
                    'english_question' => $entry->english_question,
                    'english_explanation' => $entry->english_explanation,
                    'english_figure_caption' => $entry->figure_caption,
                    'correct_answer' => $entry->correct_answer,
                    'sign_key' => $entry->sign_key,
                    'illustration_reference' => $entry->sign_key ?? $entry->figure_caption,
                    'editorial_flags' => $entry->editorial_issues ?? [],
                    'review_flags' => $entry->review_flags ?? [],
                    'answer_key_status' => $entry->answer_key_status?->value,
                    'content_rights_status' => $entry->content_rights_status?->value,
                    'editorial_workflow_status' => $entry->editorial_workflow_status?->value,
                    'locale' => $locale,
                    'question_text' => $questionText,
                    'explanation' => $explanation,
                    'figure_caption' => $figureCaption,
                    'nuance_note' => $existing?->nuance_note,
                    'status' => $status,
                    'quality_flags' => $qualityFlags,
                    'english_source_readonly' => true,
                ];
            }
        }

        return [
            'format_version' => self::FORMAT_VERSION,
            'exported_at' => now()->toIso8601String(),
            'locales' => $locales,
            'entry_count' => count($rows),
            'filters' => [
                'source_groups' => $sourceGroups,
                'entry_ids' => $entryIds,
                'exam_categories' => $examCategories,
                'study_topics' => $studyTopics,
                'source_key' => $sourceKey,
                'workflow_status' => $workflowStatus,
                'content_rights_status' => $contentRightsStatus,
                'answer_key_status' => $answerKeyStatus,
                'pending_only' => $pendingOnly,
                'limit' => $limit,
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function writeJson(string $path, array $payload): void
    {
        $directory = dirname($path);

        $storageRoot = storage_path('app');
        $normalizedDirectory = str_replace('\\', '/', $directory);
        $normalizedStorage = str_replace('\\', '/', $storageRoot);

        if (! str_starts_with($normalizedDirectory, $normalizedStorage.'/menkyo-exports')
            && $normalizedDirectory !== $normalizedStorage.'/menkyo-exports') {
            throw new RuntimeException('Translation exports must remain under storage/app/menkyo-exports.');
        }

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create export directory: '.$directory);
        }

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        if (file_put_contents($path, $json) === false) {
            throw new RuntimeException('Unable to write export file: '.$path);
        }
    }

    /**
     * Ensure a pending translation stub exists for each requested locale.
     *
     * @param  list<string>|null  $locales
     */
    public function ensurePendingStubs(?array $locales = null): int
    {
        $locales = $this->normalizeLocales($locales);
        $created = 0;

        QuestionImportEntry::query()->orderBy('id')->chunkById(100, function (Collection $entries) use ($locales, &$created): void {
            foreach ($entries as $entry) {
                foreach ($locales as $locale) {
                    $existing = QuestionImportEntryTranslation::query()
                        ->where('question_import_entry_id', $entry->id)
                        ->where('locale', $locale)
                        ->first();

                    if ($existing !== null) {
                        continue;
                    }

                    $attributes = [
                        'question_import_entry_id' => $entry->id,
                        'locale' => $locale,
                        'status' => StagedTranslationStatus::PendingTranslation,
                        'quality_flags' => [],
                    ];

                    if ($locale === 'en') {
                        $attributes['question_text'] = $entry->english_question;
                        $attributes['explanation'] = $entry->english_explanation;
                        $attributes['figure_caption'] = $entry->figure_caption;
                        $attributes['status'] = StagedTranslationStatus::Draft;
                        $attributes['translated_at'] = now();
                        $attributes['translator_note'] = 'Exact original English source retained as immutable reference copy.';
                    }

                    QuestionImportEntryTranslation::query()->create($attributes);
                    $created++;
                }
            }
        });

        return $created;
    }

    /**
     * @param  Builder<QuestionImportEntry>  $query
     * @param  list<string>|null  $sourceGroups
     * @param  list<int>|null  $entryIds
     * @param  list<string>|null  $examCategories
     * @param  list<string>|null  $studyTopics
     */
    private function applyFilters(
        Builder $query,
        ?array $sourceGroups,
        ?array $entryIds,
        ?array $examCategories,
        ?array $studyTopics,
        ?string $sourceKey,
        ?string $workflowStatus,
        ?string $contentRightsStatus,
        ?string $answerKeyStatus,
    ): void {
        if ($sourceGroups !== null && $sourceGroups !== []) {
            $query->whereIn('source_group', $sourceGroups);
        }

        if ($entryIds !== null && $entryIds !== []) {
            $query->whereIn('id', $entryIds);
        }

        if ($examCategories !== null && $examCategories !== []) {
            $query->where(function (Builder $inner) use ($examCategories): void {
                foreach ($examCategories as $category) {
                    $inner->orWhereJsonContains('editorial_categories', $category);
                }
            });
        }

        if ($studyTopics !== null && $studyTopics !== []) {
            $query->where(function (Builder $inner) use ($studyTopics): void {
                foreach ($studyTopics as $topic) {
                    $inner->orWhereJsonContains('study_topics', $topic);
                }
            });
        }

        if (filled($sourceKey)) {
            $query->whereHas('batch', fn (Builder $batch) => $batch->where('source_key', $sourceKey));
        }

        if (filled($workflowStatus)) {
            $query->where('editorial_workflow_status', $workflowStatus);
        }

        if (filled($contentRightsStatus)) {
            $query->where('content_rights_status', $contentRightsStatus);
        }

        if (filled($answerKeyStatus)) {
            $query->where('answer_key_status', $answerKeyStatus);
        }
    }

    /**
     * @param  list<string>|null  $locales
     * @return list<string>
     */
    private function normalizeLocales(?array $locales): array
    {
        if ($locales === null || $locales === []) {
            return SupportedLocale::codes();
        }

        $normalized = [];

        foreach ($locales as $locale) {
            if (! SupportedLocale::isSupported($locale)) {
                throw new InvalidArgumentException("Unsupported locale [{$locale}].");
            }

            $normalized[] = SupportedLocale::normalize($locale);
        }

        return array_values(array_unique($normalized));
    }
}
