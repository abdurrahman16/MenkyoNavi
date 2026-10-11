<?php

namespace App\Console\Commands;

use App\Services\Editorial\OriginalKarimenPilotTranslationDraftService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('menkyo:stage-original-karimen-pilot-translations {--dry-run : Preview without writing} {--commit : Persist private translation drafts}')]
#[Description('Stage controlled multilingual draft translations for eligible original Karimen pilot candidates')]
class StageOriginalKarimenPilotTranslationsCommand extends Command
{
    public function handle(OriginalKarimenPilotTranslationDraftService $drafts): int
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
            $summary = $drafts->stage(dryRun: $dryRun);
        } catch (Throwable $exception) {
            $this->error('Pilot translation staging failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info($summary['dry_run']
            ? 'Dry-run preview (no translation writes).'
            : 'Private pilot translation drafts committed (unpublished, not native-reviewed).');

        $localeRows = [];
        foreach ($summary['per_locale'] as $locale => $count) {
            $localeRows[] = [$locale, (string) $count];
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Collection key', $summary['collection_key']],
                ['Eligible Japanese sources', (string) $summary['eligible_count']],
                ['Withheld', (string) $summary['withheld_count']],
                ['Created', (string) $summary['created_count']],
                ['Updated', (string) $summary['updated_count']],
                ['Skipped', (string) $summary['skipped_count']],
                ['Protected (human-reviewed)', (string) $summary['protected_count']],
                ['Conflicted / missing', (string) $summary['conflicted_count']],
                ['Native-reviewed?', 'no'],
                ['Published?', 'no'],
            ],
        );

        $this->table(['Locale', 'Drafts touched'], $localeRows);

        foreach ($summary['withheld'] as $row) {
            $this->line('Withheld '.$row['candidate_id'].': '.$row['reason']);
        }

        $this->comment('Drafts remain private. Research support ≠ human accuracy or native translation approval.');

        return $summary['conflicted_count'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
