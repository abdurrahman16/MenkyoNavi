<?php

namespace App\Console\Commands;

use App\Services\Imports\QuestionBankImportService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

#[Signature('menkyo:import-karimen-html {--source=tuple610 : Import source alias (tuple610|object414)} {--file= : Absolute or storage-relative path to the English HTML source} {--source-key= : Stable source key for idempotent re-import (defaults per --source)} {--dry-run : Preview parse/import without writing staging records} {--commit : Persist staging records (required to write)}')]
#[Description('Safely parse and stage original English exam questions from HTML sources')]
class ImportKarimenHtmlCommand extends Command
{
    public function handle(QuestionBankImportService $importService): int
    {
        if (app()->isProduction()) {
            $this->error('This import command is disabled in production.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $commit = (bool) $this->option('commit');

        if (! $dryRun && ! $commit) {
            $this->warn('Neither --dry-run nor --commit was provided. Defaulting to --dry-run (no writes).');
            $dryRun = true;
        }

        if ($dryRun && $commit) {
            $this->error('Choose either --dry-run or --commit, not both.');

            return self::FAILURE;
        }

        try {
            $definition = $importService->resolveSourceDefinition((string) $this->option('source'));
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $sourcePath = $this->resolveSourcePath((string) ($this->option('file') ?: ''), $importService, $definition['alias']);
        $sourceKeyOption = $this->option('source-key');
        $sourceKey = is_string($sourceKeyOption) && $sourceKeyOption !== ''
            ? $sourceKeyOption
            : $definition['source_key'];

        try {
            $summary = $importService->import(
                sourcePath: $sourcePath,
                dryRun: $dryRun,
                sourceKey: $sourceKey,
                sourceAlias: $definition['alias'],
            );
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable $exception) {
            $this->error('Import failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info($summary['dry_run'] ? 'Dry-run preview (no staging writes).' : 'Import committed to staging tables.');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Source alias', $summary['source_alias']],
                ['Source key', $summary['source_key']],
                ['Source file', $summary['source_filename']],
                ['File checksum', $summary['file_checksum']],
                ['Parsed entries', (string) $summary['entry_count']],
                ['Parse errors', (string) $summary['parse_error_count']],
                ['Identical across sets', (string) $summary['duplicate_across_sets_count']],
                ['Duplicates inside source', (string) $summary['duplicates_inside_source_count']],
                ['Exact cross-source match', (string) $summary['exact_same_question_and_answer_count']],
                ['Exact Q / different answer', (string) $summary['exact_same_question_different_answer_count']],
                ['Same Q / different explanation', (string) $summary['same_question_different_explanation_count']],
                ['Similar wording (review)', (string) $summary['similar_wording_needs_review_count']],
                ['Same wording / different answer', (string) $summary['same_wording_different_answer_count']],
                ['Same-source reimports', (string) $summary['same_source_reimport_count']],
                ['Flagged for review', (string) $summary['flagged_for_review_count']],
                ['Media review needed', (string) $summary['media_review_count']],
                ['Created staging rows', (string) $summary['created_count']],
                ['Updated staging rows', (string) $summary['updated_count']],
                ['Questions table unchanged', $summary['questions_table_unchanged'] ? 'yes' : 'no'],
                ['Batch ID', $summary['batch_id'] === null ? 'n/a' : (string) $summary['batch_id']],
            ],
        );

        if ($summary['group_counts'] !== []) {
            $this->newLine();
            $this->info('Entries by examination group:');
            $this->table(
                ['Group', 'Count'],
                collect($summary['group_counts'])
                    ->map(fn (int $count, string $group): array => [$group, (string) $count])
                    ->values()
                    ->all(),
            );
        }

        if ($summary['parse_errors'] !== []) {
            $this->newLine();
            $this->warn('Parse errors:');
            foreach ($summary['parse_errors'] as $error) {
                $context = $error['context'] ?? 'unknown';
                $this->line(" - [{$context}] {$error['message']}");
            }
        }

        if ($summary['warnings'] !== []) {
            $this->newLine();
            $this->warn('Warnings:');
            foreach ($summary['warnings'] as $warning) {
                $this->line(' - '.$warning);
            }
        }

        if (! $summary['dry_run'] && $summary['entry_count'] > 0) {
            $this->comment('Staging records remain unpublished. Content-rights and answer-key review are still required before publication.');
            $this->comment('Imported material is not labeled as verified official examination questions.');
        }

        return $summary['parse_error_count'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function resolveSourcePath(
        string $fileOption,
        QuestionBankImportService $importService,
        string $sourceAlias,
    ): string {
        if ($fileOption === '') {
            return $importService->defaultSourcePath($sourceAlias);
        }

        if (is_file($fileOption)) {
            return $fileOption;
        }

        $fromStorage = storage_path('app/'.ltrim(str_replace('\\', '/', $fileOption), '/'));

        if (is_file($fromStorage)) {
            return $fromStorage;
        }

        return $fileOption;
    }
}
