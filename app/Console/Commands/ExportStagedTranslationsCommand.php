<?php

namespace App\Console\Commands;

use App\Services\Translations\StagedTranslationExportService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('menkyo:translations-export {--locale=* : Locale codes to export (default: all supported)} {--group=* : Optional source groups} {--id=* : Optional staged entry IDs} {--exam-category=* : Editorial exam categories} {--study-topic=* : Study topic filters} {--source-key= : Batch source key} {--workflow= : Editorial workflow status} {--limit= : Maximum entries to export} {--pending-only : Export only pending/incomplete rows} {--ensure-stubs : Create pending translation stubs before export} {--path= : Output JSON path}')]
#[Description('Export staged examination translations for external review')]
class ExportStagedTranslationsCommand extends Command
{
    public function handle(StagedTranslationExportService $exportService): int
    {
        if (app()->isProduction()) {
            $this->error('This command is disabled in production.');

            return self::FAILURE;
        }

        try {
            $locales = $this->option('locale') ?: null;
            $groups = $this->option('group') ?: null;
            $ids = array_map('intval', $this->option('id') ?: []);
            $limit = $this->option('limit') !== null ? (int) $this->option('limit') : 25;

            if ($this->option('ensure-stubs')) {
                $created = $exportService->ensurePendingStubs($locales);
                $this->info("Created {$created} pending translation stubs.");
            }

            $payload = $exportService->export(
                locales: $locales,
                sourceGroups: $groups,
                pendingOnly: (bool) $this->option('pending-only'),
                entryIds: $ids !== [] ? $ids : null,
                examCategories: $this->option('exam-category') ?: null,
                studyTopics: $this->option('study-topic') ?: null,
                sourceKey: $this->option('source-key') ?: null,
                workflowStatus: $this->option('workflow') ?: null,
                limit: $limit > 0 ? $limit : null,
            );

            $path = (string) ($this->option('path') ?: storage_path(
                'app/menkyo-exports/translations-'.now()->format('Ymd-His').'.json'
            ));

            $exportService->writeJson($path, $payload);

            $this->info('Exported '.$payload['entry_count'].' translation rows.');
            $this->line('Output: '.$path);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
