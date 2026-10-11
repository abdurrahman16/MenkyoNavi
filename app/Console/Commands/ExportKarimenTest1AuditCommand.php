<?php

namespace App\Console\Commands;

use App\Services\Editorial\KarimenTest1AuditService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('menkyo:karimen-test-1-audit {--json-path= : Optional JSON output path under storage/app/menkyo-exports} {--markdown-path= : Optional Markdown output path under storage/app/menkyo-exports}')]
#[Description('Export a private Karimen Test 1 editorial/legal readiness audit (does not publish or mutate answers)')]
class ExportKarimenTest1AuditCommand extends Command
{
    public function handle(KarimenTest1AuditService $auditService): int
    {
        if (app()->isProduction()) {
            $this->error('This command is disabled in production.');

            return self::FAILURE;
        }

        try {
            $audit = $auditService->audit();
            $paths = $auditService->writePrivateReports(
                $audit,
                $this->option('json-path') ?: null,
                $this->option('markdown-path') ?: null,
            );
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $ready = $audit['readiness'];
        $task16 = $audit['task_16_state'];

        $this->info('Karimen Test 1 private audit exported.');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Questions audited', (string) $ready['total_questions']],
                ['Japanese drafts', (string) $task16['japanese_drafts']],
                ['Eight-language complete', (string) $task16['eight_language_complete']],
                ['Task 16 complete', $task16['task_16_complete'] ? 'yes' : 'no'],
                ['Verified answer keys', (string) $ready['verified_answer_keys']],
                ['Rights cleared', (string) $ready['rights_cleared_questions']],
                ['Publication-ready', (string) $ready['fully_publication_ready_questions']],
                ['JSON', $paths['json_path']],
                ['Markdown', $paths['markdown_path']],
            ],
        );

        return self::SUCCESS;
    }
}
