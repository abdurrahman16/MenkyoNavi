<?php

namespace App\Console\Commands;

use App\Services\Editorial\KarimenTest1PriorityReviewService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('menkyo:karimen-test-1-priority-review {--json-path= : Optional JSON output path under storage/app/menkyo-exports} {--markdown-path= : Optional Markdown output path under storage/app/menkyo-exports}')]
#[Description('Export private Karimen Test 1 priority review packets (Task 33; does not verify answers or clear rights)')]
class KarimenTest1PriorityReviewCommand extends Command
{
    public function handle(KarimenTest1PriorityReviewService $priorityReview): int
    {
        if (app()->isProduction()) {
            $this->error('This command is disabled in production.');

            return self::FAILURE;
        }

        try {
            $report = $priorityReview->build();
            $paths = $priorityReview->writePrivateReports(
                $report,
                $this->option('json-path') ?: null,
                $this->option('markdown-path') ?: null,
            );
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $blockers = $report['section_e_remaining_blockers'];
        $rights = $report['section_c_source_rights_review'];

        $this->info('Karimen Test 1 priority review exported (private).');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Priority questions', (string) count($report['section_a_priority_questions'])],
                ['Figure-dependent entries', (string) count($report['section_b_figure_inventory'])],
                ['Batch rights status', (string) ($rights['content_rights_status'] ?? '—')],
                ['Publication-ready', (string) $blockers['publication_ready_count']],
                ['Blocked', (string) $blockers['blocked_count']],
                ['JSON', $paths['json_path']],
                ['Markdown', $paths['markdown_path']],
            ],
        );

        return self::SUCCESS;
    }
}
