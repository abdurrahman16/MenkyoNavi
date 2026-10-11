<?php

namespace App\Console\Commands;

use App\Services\Translations\StagedTranslationImportService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('menkyo:translations-import {file : Path to translation JSON} {--dry-run : Validate without writing} {--commit : Persist translation rows} {--force-reviewed : Allow overwriting reviewed translations}')]
#[Description('Import reviewed examination translations into staging (never edits English source)')]
class ImportStagedTranslationsCommand extends Command
{
    public function handle(StagedTranslationImportService $importService): int
    {
        if (app()->isProduction()) {
            $this->error('This command is disabled in production.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $commit = (bool) $this->option('commit');

        if (! $dryRun && ! $commit) {
            $this->warn('Neither --dry-run nor --commit provided. Defaulting to --dry-run.');
            $dryRun = true;
        }

        if ($dryRun && $commit) {
            $this->error('Choose either --dry-run or --commit, not both.');

            return self::FAILURE;
        }

        try {
            $summary = $importService->importFromFile(
                path: (string) $this->argument('file'),
                dryRun: $dryRun,
                forceReviewedOverwrite: (bool) $this->option('force-reviewed'),
            );
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info($summary['dry_run'] ? 'Dry-run validation complete.' : 'Translation import committed.');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Rows', (string) $summary['row_count']],
                ['Created', (string) $summary['created_count']],
                ['Updated', (string) $summary['updated_count']],
                ['Skipped', (string) $summary['skipped_count']],
                ['Blocked reviewed', (string) $summary['blocked_reviewed_count']],
                ['Duplicate rows', (string) $summary['duplicate_row_count']],
                ['Validation errors', (string) $summary['validation_error_count']],
                ['Rows with quality flags', (string) $summary['quality_flag_count']],
                ['English mutation attempts blocked', (string) $summary['english_mutations_blocked']],
            ],
        );

        foreach (array_slice($summary['errors'], 0, 20) as $error) {
            $this->line(' - '.$error);
        }

        if (count($summary['errors']) > 20) {
            $this->line(' - … '.(count($summary['errors']) - 20).' more');
        }

        return ($summary['validation_error_count'] > 0 || $summary['blocked_reviewed_count'] > 0)
            ? self::FAILURE
            : self::SUCCESS;
    }
}
