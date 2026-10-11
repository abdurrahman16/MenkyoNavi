<?php

namespace App\Services\Imports;

/**
 * Backward-compatible facade for the original tuple HTML importer.
 *
 * @phpstan-type ImportSummary array{
 *     dry_run: bool,
 *     source_path: string,
 *     source_filename: string,
 *     file_checksum: string,
 *     entry_count: int,
 *     group_counts: array<string, int>,
 *     parse_error_count: int,
 *     parse_errors: list<array{message: string, context: ?string}>,
 *     warnings: list<string>,
 *     duplicate_across_sets_count: int,
 *     same_wording_different_answer_count: int,
 *     same_source_reimport_count: int,
 *     flagged_for_review_count: int,
 *     media_review_count: int,
 *     created_count: int,
 *     updated_count: int,
 *     questions_table_unchanged: bool,
 *     batch_id: ?int
 * }
 */
class KarimenQuestionImportService
{
    public const DEFAULT_SOURCE_KEY = 'karimen_exam_trainer_en_610';

    public const DEFAULT_RELATIVE_PATH = 'menkyo-imports/karimen_exam_trainer (1).html';

    public function __construct(
        private QuestionBankImportService $importService,
    ) {}

    public function defaultSourcePath(): string
    {
        return $this->importService->defaultSourcePath(QuestionBankImportService::SOURCE_TUPLE_610);
    }

    /**
     * @return ImportSummary
     */
    public function import(string $sourcePath, bool $dryRun = true, string $sourceKey = self::DEFAULT_SOURCE_KEY): array
    {
        $summary = $this->importService->import(
            sourcePath: $sourcePath,
            dryRun: $dryRun,
            sourceKey: $sourceKey,
            sourceAlias: QuestionBankImportService::SOURCE_TUPLE_610,
        );

        return [
            'dry_run' => $summary['dry_run'],
            'source_path' => $summary['source_path'],
            'source_filename' => $summary['source_filename'],
            'file_checksum' => $summary['file_checksum'],
            'entry_count' => $summary['entry_count'],
            'group_counts' => $summary['group_counts'],
            'parse_error_count' => $summary['parse_error_count'],
            'parse_errors' => $summary['parse_errors'],
            'warnings' => $summary['warnings'],
            'duplicate_across_sets_count' => $summary['duplicate_across_sets_count'],
            'same_wording_different_answer_count' => $summary['same_wording_different_answer_count'],
            'same_source_reimport_count' => $summary['same_source_reimport_count'],
            'flagged_for_review_count' => $summary['flagged_for_review_count'],
            'media_review_count' => $summary['media_review_count'],
            'created_count' => $summary['created_count'],
            'updated_count' => $summary['updated_count'],
            'questions_table_unchanged' => $summary['questions_table_unchanged'],
            'batch_id' => $summary['batch_id'],
        ];
    }

    public function ensureFixtureCopiedToStorage(?string $fixturePath = null): string
    {
        return $this->importService->ensureFixtureCopiedToStorage(
            $fixturePath,
            QuestionBankImportService::SOURCE_TUPLE_610,
        );
    }
}
