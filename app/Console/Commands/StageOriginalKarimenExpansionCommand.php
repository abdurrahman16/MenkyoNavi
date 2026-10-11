<?php

namespace App\Console\Commands;

use App\Services\Editorial\OriginalKarimenExpansionStagingService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('menkyo:stage-original-karimen-expansion {--dry-run : Preview without writing} {--commit : Persist unpublished expansion staging records} {--batch= : Optional batch letter A|B|C|D}')]
#[Description('Stage MenkyoNavi original Japanese Karimen expansion candidates (11–50) for private editorial review')]
class StageOriginalKarimenExpansionCommand extends Command
{
    public function handle(OriginalKarimenExpansionStagingService $staging): int
    {
        if (app()->isProduction()) {
            $this->error('This command is disabled in production.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $commit = (bool) $this->option('commit');
        $batch = $this->option('batch');
        $batch = is_string($batch) && $batch !== '' ? strtoupper($batch) : null;

        if (! $dryRun && ! $commit) {
            $this->warn('Neither --dry-run nor --commit provided. Defaulting to --dry-run.');
            $dryRun = true;
        }

        if ($dryRun && $commit) {
            $this->error('Choose either --dry-run or --commit, not both.');

            return self::FAILURE;
        }

        if ($batch !== null && ! in_array($batch, OriginalKarimenExpansionStagingService::BATCHES, true)) {
            $this->error('Invalid --batch. Use A, B, C, or D.');

            return self::FAILURE;
        }

        try {
            $summary = $staging->stage(dryRun: $dryRun, batch: $batch);
        } catch (Throwable $exception) {
            $this->error('Staging failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info($summary['dry_run']
            ? 'Dry-run preview (no staging writes).'
            : 'Original Karimen expansion committed to staging (unpublished).');

        $this->table(
            ['Metric', 'Value'],
            [
                ['Collection key', $summary['collection_key']],
                ['Batch filter', $summary['batch_filter'] ?? 'all'],
                ['Candidates', (string) $summary['candidate_count']],
                ['Created', (string) $summary['created_count']],
                ['Updated', (string) $summary['updated_count']],
                ['Skipped (human-protected)', (string) $summary['skipped_protected_count']],
                ['supported_as_written', (string) $summary['supported_as_written_count']],
                ['supported_with_clarification', (string) $summary['supported_with_clarification_count']],
                ['Batch id', $summary['batch_id'] === null ? 'n/a' : (string) $summary['batch_id']],
            ],
        );

        $this->line('Admin filter: source_key='.OriginalKarimenExpansionStagingService::COLLECTION_KEY);
        $this->line('Publication, accuracy, editorial, and rights approvals remain pending.');
        $this->line('Task 48 does not generate multilingual translation drafts.');

        return self::SUCCESS;
    }
}
