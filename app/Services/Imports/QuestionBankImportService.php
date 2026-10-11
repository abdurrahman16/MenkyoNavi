<?php

namespace App\Services\Imports;

use App\Contracts\HtmlQuestionBankParser;
use App\Enums\QuestionImportDuplicateKind;
use App\Enums\QuestionImportMediaStatus;
use App\Enums\QuestionImportStatus;
use App\Models\Question;
use App\Models\QuestionImportBatch;
use App\Models\QuestionImportEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use RuntimeException;

/**
 * Generic HTML question-bank importer with source-specific parsers.
 *
 * @phpstan-type ImportSummary array{
 *     dry_run: bool,
 *     source_alias: string,
 *     source_path: string,
 *     source_filename: string,
 *     source_key: string,
 *     file_checksum: string,
 *     entry_count: int,
 *     group_counts: array<string, int>,
 *     parse_error_count: int,
 *     parse_errors: list<array{message: string, context: ?string}>,
 *     warnings: list<string>,
 *     duplicate_across_sets_count: int,
 *     same_wording_different_answer_count: int,
 *     exact_same_question_and_answer_count: int,
 *     exact_same_question_different_answer_count: int,
 *     same_question_different_explanation_count: int,
 *     similar_wording_needs_review_count: int,
 *     duplicates_inside_source_count: int,
 *     same_source_reimport_count: int,
 *     flagged_for_review_count: int,
 *     media_review_count: int,
 *     created_count: int,
 *     updated_count: int,
 *     questions_table_unchanged: bool,
 *     batch_id: ?int
 * }
 */
class QuestionBankImportService
{
    public const SOURCE_TUPLE_610 = 'tuple610';

    public const SOURCE_OBJECT_414 = 'object414';

    /**
     * @var array<string, array{
     *     alias: string,
     *     source_key: string,
     *     relative_path: string,
     *     parser: class-string<HtmlQuestionBankParser>,
     *     label: string
     * }>
     */
    public const SOURCES = [
        self::SOURCE_TUPLE_610 => [
            'alias' => self::SOURCE_TUPLE_610,
            'source_key' => 'karimen_exam_trainer_en_610',
            'relative_path' => 'menkyo-imports/karimen_exam_trainer (1).html',
            'parser' => KarimenHtmlQuestionParser::class,
            'label' => 'Tuple HTML bank (expected ~610)',
        ],
        self::SOURCE_OBJECT_414 => [
            'alias' => self::SOURCE_OBJECT_414,
            'source_key' => 'karimen_exam_trainer_en_414',
            'relative_path' => 'menkyo-imports/karimen_exam_trainer.html',
            'parser' => KarimenObjectHtmlQuestionParser::class,
            'label' => 'Object-literal HTML bank (expected ~414)',
        ],
    ];

    public function __construct(
        private CrossSourceDuplicateAuditor $duplicateAuditor,
    ) {}

    /**
     * @return list<string>
     */
    public function sourceAliases(): array
    {
        return array_keys(self::SOURCES);
    }

    /**
     * @return array{
     *     alias: string,
     *     source_key: string,
     *     relative_path: string,
     *     parser: class-string<HtmlQuestionBankParser>,
     *     label: string
     * }
     */
    public function resolveSourceDefinition(string $source): array
    {
        $normalized = strtolower(trim($source));

        $aliases = [
            'tuple' => self::SOURCE_TUPLE_610,
            'tuple610' => self::SOURCE_TUPLE_610,
            '610' => self::SOURCE_TUPLE_610,
            'karimen_exam_trainer_en_610' => self::SOURCE_TUPLE_610,
            'object' => self::SOURCE_OBJECT_414,
            'object414' => self::SOURCE_OBJECT_414,
            '414' => self::SOURCE_OBJECT_414,
            'karimen_exam_trainer_en_414' => self::SOURCE_OBJECT_414,
            'karimen_exam_trainer.html' => self::SOURCE_OBJECT_414,
        ];

        $alias = $aliases[$normalized] ?? $normalized;

        if (! isset(self::SOURCES[$alias])) {
            throw new InvalidArgumentException(
                'Unknown import source "'.$source.'". Supported: '.implode(', ', $this->sourceAliases())
            );
        }

        return self::SOURCES[$alias];
    }

    public function defaultSourcePath(string $sourceAlias = self::SOURCE_TUPLE_610): string
    {
        $definition = $this->resolveSourceDefinition($sourceAlias);

        return storage_path('app/'.$definition['relative_path']);
    }

    /**
     * @return ImportSummary
     */
    public function import(
        string $sourcePath,
        bool $dryRun = true,
        ?string $sourceKey = null,
        string $sourceAlias = self::SOURCE_TUPLE_610,
    ): array {
        $definition = $this->resolveSourceDefinition($sourceAlias);
        $sourceKey ??= $definition['source_key'];

        if (! is_file($sourcePath)) {
            throw new RuntimeException(
                'Import source HTML file was not found: '.$sourcePath
                ."\nPlace the original file at storage/app/".$definition['relative_path']
                .' (or pass --file=). Substitute questions are not generated.'
            );
        }

        $absolutePath = (string) realpath($sourcePath);
        $fileChecksum = hash_file('sha256', $absolutePath);

        if ($fileChecksum === false) {
            throw new RuntimeException('Unable to checksum import source: '.$absolutePath);
        }

        /** @var HtmlQuestionBankParser $parser */
        $parser = app($definition['parser']);
        $parseResult = $parser->parseFile($absolutePath, $fileChecksum);
        $questionsBefore = Question::query()->count();

        $duplicateReport = $this->duplicateAuditor->analyze($parseResult->entries, $sourceKey);

        $summary = [
            'dry_run' => $dryRun,
            'source_alias' => $definition['alias'],
            'source_path' => $absolutePath,
            'source_filename' => basename($absolutePath),
            'source_key' => $sourceKey,
            'file_checksum' => $fileChecksum,
            'entry_count' => $parseResult->entryCount(),
            'group_counts' => $parseResult->groupCounts,
            'parse_error_count' => count($parseResult->errors),
            'parse_errors' => $parseResult->errors,
            'warnings' => $parseResult->warnings,
            'duplicate_across_sets_count' => $duplicateReport['identical_across_sets'],
            'same_wording_different_answer_count' => $duplicateReport['same_wording_different_answer'],
            'exact_same_question_and_answer_count' => $duplicateReport['exact_same_question_and_answer'],
            'exact_same_question_different_answer_count' => $duplicateReport['exact_same_question_different_answer'],
            'same_question_different_explanation_count' => $duplicateReport['same_question_different_explanation'],
            'similar_wording_needs_review_count' => $duplicateReport['similar_wording_needs_review'],
            'duplicates_inside_source_count' => $duplicateReport['duplicates_inside_source'],
            'same_source_reimport_count' => 0,
            'flagged_for_review_count' => 0,
            'media_review_count' => 0,
            'created_count' => 0,
            'updated_count' => 0,
            'questions_table_unchanged' => true,
            'batch_id' => null,
        ];

        foreach ($parseResult->entries as $entry) {
            if ($entry['review_flags'] !== []) {
                $summary['flagged_for_review_count']++;
            }

            if (in_array($entry['media_availability'], [
                QuestionImportMediaStatus::MissingSignKey->value,
                QuestionImportMediaStatus::NeedsReview->value,
            ], true)) {
                $summary['media_review_count']++;
            }
        }

        if ($dryRun || $parseResult->entryCount() === 0) {
            $summary['questions_table_unchanged'] = Question::query()->count() === $questionsBefore;

            return $summary;
        }

        return DB::transaction(function () use (
            $absolutePath,
            $fileChecksum,
            $parseResult,
            $duplicateReport,
            $sourceKey,
            $questionsBefore,
            $summary,
            $definition,
        ): array {
            $batch = QuestionImportBatch::query()->updateOrCreate(
                ['source_key' => $sourceKey],
                [
                    'source_filename' => basename($absolutePath),
                    'source_relative_path' => $this->relativeStoragePath($absolutePath, $definition['relative_path']),
                    'file_checksum' => $fileChecksum,
                    'source_locale' => 'en',
                    'status' => QuestionImportStatus::Staged->value,
                    'entry_count' => $parseResult->entryCount(),
                    'parse_error_count' => count($parseResult->errors),
                    'duplicate_across_sets_count' => $duplicateReport['identical_across_sets'],
                    'same_wording_different_answer_count' => $duplicateReport['same_wording_different_answer'],
                    'flagged_for_review_count' => $summary['flagged_for_review_count'],
                    'media_review_count' => $summary['media_review_count'],
                    'group_counts' => $parseResult->groupCounts,
                    'parse_errors' => $parseResult->errors,
                    'summary' => [
                        'warnings' => $parseResult->warnings,
                        'sign_key_count' => count($parseResult->signKeys),
                        'source_alias' => $definition['alias'],
                        'duplicate_audit' => [
                            'exact_same_question_and_answer' => $duplicateReport['exact_same_question_and_answer'],
                            'exact_same_question_different_answer' => $duplicateReport['exact_same_question_different_answer'],
                            'same_question_different_explanation' => $duplicateReport['same_question_different_explanation'],
                            'similar_wording_needs_review' => $duplicateReport['similar_wording_needs_review'],
                            'duplicates_inside_source' => $duplicateReport['duplicates_inside_source'],
                        ],
                    ],
                    'imported_at' => now(),
                ],
            );

            $created = 0;
            $updated = 0;
            $reimportCount = 0;
            $savedByChecksum = [];

            foreach ($parseResult->entries as $entry) {
                $duplicateMeta = $duplicateReport['by_checksum'][$entry['source_entry_checksum']] ?? [
                    'kind' => QuestionImportDuplicateKind::None->value,
                    'of_checksum' => null,
                    'of_entry_id' => null,
                ];

                $importStatus = $entry['review_flags'] === []
                    ? QuestionImportStatus::Staged
                    : QuestionImportStatus::FlaggedForReview;

                if (in_array($entry['media_availability'], [
                    QuestionImportMediaStatus::MissingSignKey->value,
                    QuestionImportMediaStatus::NeedsReview->value,
                ], true)) {
                    $importStatus = QuestionImportStatus::NeedsMediaReview;
                }

                if (in_array($duplicateMeta['kind'], [
                    QuestionImportDuplicateKind::ExactQuestionDifferentAnswer->value,
                    QuestionImportDuplicateKind::SameWordingDifferentAnswer->value,
                    QuestionImportDuplicateKind::SameQuestionDifferentExplanation->value,
                    QuestionImportDuplicateKind::SimilarWordingNeedsReview->value,
                ], true)) {
                    $importStatus = QuestionImportStatus::FlaggedForReview;
                    $entry['review_flags'] = array_values(array_unique([
                        ...$entry['review_flags'],
                        'duplicate_audit_'.$duplicateMeta['kind'],
                    ]));
                }

                $attributes = [
                    'exam_family' => $entry['exam_family'],
                    'english_question' => $entry['english_question'],
                    'correct_answer' => $entry['correct_answer'],
                    'english_explanation' => $entry['english_explanation'],
                    'sign_key' => $entry['sign_key'],
                    'figure_caption' => $entry['figure_caption'],
                    'source_entry_checksum' => $entry['source_entry_checksum'],
                    'content_fingerprint' => $entry['content_fingerprint'],
                    'question_text_fingerprint' => $entry['question_text_fingerprint'],
                    'import_status' => $importStatus->value,
                    'media_availability' => $entry['media_availability'],
                    'duplicate_kind' => $duplicateMeta['kind'],
                    'review_flags' => $entry['review_flags'],
                    'source_metadata' => $entry['source_metadata'] ?? null,
                    'is_published' => false,
                ];

                $existing = QuestionImportEntry::query()
                    ->where('question_import_batch_id', $batch->id)
                    ->where('source_group', $entry['source_group'])
                    ->where('original_question_number', $entry['original_question_number'])
                    ->first();

                if ($existing !== null) {
                    if ($attributes['duplicate_kind'] === QuestionImportDuplicateKind::None->value) {
                        $attributes['duplicate_kind'] = QuestionImportDuplicateKind::SameSourceReimport->value;
                    }

                    $existing->fill($attributes);
                    $existing->duplicate_of_entry_id = $duplicateMeta['of_entry_id'] ?? null;
                    $existing->save();
                    $updated++;
                    $reimportCount++;
                    $savedByChecksum[$entry['source_entry_checksum']] = $existing;
                } else {
                    $model = QuestionImportEntry::query()->create([
                        ...$attributes,
                        'question_import_batch_id' => $batch->id,
                        'source_group' => $entry['source_group'],
                        'original_question_number' => $entry['original_question_number'],
                        'duplicate_of_entry_id' => $duplicateMeta['of_entry_id'] ?? null,
                    ]);
                    $created++;
                    $savedByChecksum[$entry['source_entry_checksum']] = $model;
                }
            }

            foreach ($parseResult->entries as $entry) {
                $duplicateMeta = $duplicateReport['by_checksum'][$entry['source_entry_checksum']] ?? null;

                if ($duplicateMeta === null || ($duplicateMeta['of_checksum'] ?? null) === null) {
                    continue;
                }

                if (($duplicateMeta['of_entry_id'] ?? null) !== null) {
                    continue;
                }

                $model = $savedByChecksum[$entry['source_entry_checksum']] ?? null;
                $of = $savedByChecksum[$duplicateMeta['of_checksum']] ?? null;

                if ($model === null || $of === null) {
                    continue;
                }

                $model->duplicate_of_entry_id = $of->id;
                $model->save();
            }

            $batch->forceFill([
                'entry_count' => QuestionImportEntry::query()
                    ->where('question_import_batch_id', $batch->id)
                    ->count(),
            ])->save();

            $summary['batch_id'] = $batch->id;
            $summary['created_count'] = $created;
            $summary['updated_count'] = $updated;
            $summary['same_source_reimport_count'] = $reimportCount;
            $summary['questions_table_unchanged'] = Question::query()->count() === $questionsBefore;

            return $summary;
        });
    }

    private function relativeStoragePath(string $absolutePath, string $fallback): string
    {
        $storageApp = realpath(storage_path('app'));

        if ($storageApp !== false) {
            $normalizedStorage = str_replace('\\', '/', $storageApp);
            $normalizedPath = str_replace('\\', '/', $absolutePath);

            if (str_starts_with($normalizedPath, $normalizedStorage.'/')) {
                return ltrim(substr($normalizedPath, strlen($normalizedStorage)), '/');
            }
        }

        return $fallback;
    }

    public function ensureFixtureCopiedToStorage(?string $fixturePath = null, string $sourceAlias = self::SOURCE_TUPLE_610): string
    {
        $target = $this->defaultSourcePath($sourceAlias);
        $fixturePath ??= match ($sourceAlias) {
            self::SOURCE_OBJECT_414 => base_path('tests/Fixtures/Imports/karimen_exam_trainer_object.html'),
            default => base_path('tests/Fixtures/Imports/karimen_exam_trainer.html'),
        };

        if (! is_file($target) && is_file($fixturePath)) {
            File::ensureDirectoryExists(dirname($target));
            File::copy($fixturePath, $target);
        }

        return $target;
    }
}
