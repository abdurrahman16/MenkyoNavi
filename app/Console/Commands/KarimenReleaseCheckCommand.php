<?php

namespace App\Console\Commands;

use App\Models\QuestionImportEntry;
use App\Services\Editorial\KarimenReleaseReadinessService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('menkyo:karimen-release-check {--json : Output machine-readable JSON}')]
#[Description('Read-only release readiness report for the original Japanese Karimen 50 (does not mutate records)')]
class KarimenReleaseCheckCommand extends Command
{
    public function handle(KarimenReleaseReadinessService $readiness): int
    {
        $beforeChecksum = $this->stagingChecksum();
        $report = $readiness->report();
        $afterChecksum = $this->stagingChecksum();

        if ($beforeChecksum !== $afterChecksum) {
            $this->error('Release check mutated staging data unexpectedly. Aborting output.');

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->info('Karimen release readiness (read-only)');
        $this->table(['Metric', 'Value'], [
            ['Total original Japanese candidates', (string) $report['total_original_japanese_candidates']],
            ['Privately staged count', (string) $report['privately_staged_count']],
            ['Source-supported count', (string) $report['source_supported_count']],
            ['Clarification-pending count', (string) $report['clarification_pending_count']],
            ['Human accuracy accepted count', (string) $report['human_accuracy_accepted_count']],
            ['Answer-key verified count', (string) $report['answer_key_verified_count']],
            ['Editorial approved count', (string) $report['editorial_approved_count']],
            ['Rights/provenance cleared count', (string) $report['rights_cleared_count']],
            ['Published count', (string) $report['published_count']],
            ['Japanese live-pool eligible count', (string) $report['japanese_live_pool_eligible_count']],
            ['Japanese public Karimen available', $report['japanese_public_karimen_can_start'] ? 'yes' : 'no'],
            ['Public release ready', $report['public_release_ready'] ? 'yes' : 'no'],
        ]);

        $this->line('Clarification IDs: '.implode(', ', $report['clarification_pending_ids'] ?: ['(none)']));
        $this->line('Human accuracy pending: '.implode(', ', $report['blocking']['human_accuracy_pending'] ?: ['(none)']));
        $this->line('Unresolved source revisions: '.implode(', ', $report['blocking']['unresolved_source_revision'] ?: ['(none)']));
        $this->line('Honmen JA can start: '.(($report['honmen']['can_start'] ?? false) ? 'yes' : 'no')
            .' (std '.$report['honmen']['available_standard'].'/'.$report['honmen']['required_standard']
            .', ill '.$report['honmen']['available_illustrated'].'/'.$report['honmen']['required_illustrated'].')');
        $this->line('Gaimen JA can start: '.(($report['gaimen']['can_start'] ?? false) ? 'yes' : 'no')
            .' (std '.$report['gaimen']['available_standard'].'/'.$report['gaimen']['required_standard'].')');

        foreach ($report['locale_availability'] as $locale => $availability) {
            $this->line(sprintf(
                'Locale %s full Karimen: %s (available %d / required %d)',
                $locale,
                $availability['can_start'] ? 'available' : 'blocked',
                $availability['available_standard'],
                $availability['required_standard'],
            ));
        }

        $this->comment($report['workflow_note']);
        $this->comment('No records were modified.');

        return self::SUCCESS;
    }

    private function stagingChecksum(): string
    {
        $rows = QuestionImportEntry::query()
            ->orderBy('id')
            ->get(['id', 'updated_at', 'is_published', 'answer_key_status', 'content_rights_status', 'accuracy_evidence_status', 'publication_blockers', 'source_metadata', 'review_flags'])
            ->toJson();

        return hash('sha256', $rows);
    }
}
