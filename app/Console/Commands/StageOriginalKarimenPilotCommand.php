<?php

namespace App\Console\Commands;

use App\Services\Editorial\OriginalKarimenPilotStagingService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('menkyo:stage-original-karimen-pilot {--dry-run : Preview without writing} {--commit : Persist unpublished pilot staging records}')]
#[Description('Stage MenkyoNavi original Japanese Karimen pilot candidates for private editorial review')]
class StageOriginalKarimenPilotCommand extends Command
{
    public function handle(OriginalKarimenPilotStagingService $staging): int
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
            $summary = $staging->stage(dryRun: $dryRun);
        } catch (Throwable $exception) {
            $this->error('Staging failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info($summary['dry_run']
            ? 'Dry-run preview (no staging writes).'
            : 'Original Karimen pilot committed to staging (unpublished).');

        $this->table(
            ['Metric', 'Value'],
            [
                ['Collection key', $summary['collection_key']],
                ['Candidates', (string) $summary['candidate_count']],
                ['Created', (string) $summary['created_count']],
                ['Updated', (string) $summary['updated_count']],
                ['Skipped (human-protected)', (string) $summary['skipped_protected_count']],
                ['Withheld topics', (string) $summary['withheld_count']],
                ['Batch id', $summary['batch_id'] === null ? 'n/a' : (string) $summary['batch_id']],
            ],
        );

        $this->line('Admin filter: source_key='.OriginalKarimenPilotStagingService::COLLECTION_KEY);
        $this->line('Publication, accuracy, editorial, and rights approvals remain pending.');

        return self::SUCCESS;
    }
}
