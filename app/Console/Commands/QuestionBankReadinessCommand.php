<?php

namespace App\Console\Commands;

use App\Services\Translations\QuestionBankReadinessReporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('menkyo:question-readiness')]
#[Description('Development-only question bank readiness report for staged/translated/reviewed/published states')]
class QuestionBankReadinessCommand extends Command
{
    public function handle(QuestionBankReadinessReporter $reporter): int
    {
        if (app()->isProduction()) {
            $this->error('This command is disabled in production.');

            return self::FAILURE;
        }

        $report = $reporter->report();

        $this->info('MenkyoNavi question-bank readiness');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Staged questions (all sources)', (string) $report['staged_questions_total']],
                ['Unique content fingerprints', (string) $report['unique_content_fingerprints']],
                ['Conflicting answers', (string) $report['conflicting_answer_count']],
                ['Publication-ready', (string) $report['publication_ready_questions']],
                ['Blocked from publishing', (string) $report['questions_blocked_from_publishing']],
                ['Draft promoted', (string) $report['draft_promoted_questions']],
                ['Published non-demo', (string) $report['published_non_demo_questions']],
                ['Missing illustrated groups', (string) $report['missing_illustrated_groups']],
                ['Full mock requirements met', $report['full_mock_requirements_met'] ? 'yes' : 'no'],
                ['Bank ready for public exams', $report['question_bank_ready_for_public_exams'] ? 'yes' : 'no'],
            ],
        );

        $this->newLine();
        $this->info('Staged entries per source');
        $this->table(
            ['Source key', 'Entries'],
            collect($report['staged_entries_per_source'])
                ->map(fn (int $count, string $key): array => [$key, (string) $count])
                ->values()
                ->all() ?: [['(none)', '0']],
        );

        $this->newLine();
        $this->info('Duplicate / conflict audit');
        $this->table(
            ['Kind', 'Count'],
            collect($report['duplicate_counts'])
                ->map(fn (int $count, string $kind): array => [$kind, (string) $count])
                ->values()
                ->all(),
        );

        $this->newLine();
        $this->info('Translations with text / draft / reviewed by locale');
        $rows = [];

        foreach ($report['translations_with_text_by_locale'] as $locale => $count) {
            $rows[] = [
                $locale,
                (string) $count,
                (string) ($report['draft_translations_by_locale'][$locale] ?? 0),
                (string) ($report['reviewed_translations_by_locale'][$locale] ?? 0),
            ];
        }

        $this->table(['Locale', 'With text', 'Draft', 'Reviewed+'], $rows);

        $this->newLine();
        $this->info('Answer-key verification');
        $this->table(
            ['Status', 'Count'],
            collect($report['answer_key_verification'])
                ->map(fn (int $count, string $status): array => [$status, (string) $count])
                ->values()
                ->all(),
        );

        $this->newLine();
        $this->info('Content-rights approval');
        $this->table(
            ['Status', 'Count'],
            collect($report['content_rights_approval'])
                ->map(fn (int $count, string $status): array => [$status, (string) $count])
                ->values()
                ->all(),
        );

        $this->newLine();
        $this->info('Honmen illustration audit');
        $honmen = $report['honmen_audit'];
        $this->table(
            ['Metric', 'Value'],
            [
                ['Honmen staged entries', (string) $honmen['honmen_entry_count']],
                ['Entries with sign key', (string) $honmen['entries_with_sign_key']],
                ['Illustrated groups in source', (string) $honmen['illustrated_group_records_found']],
                ['Engine needs illustrated groups', (string) $honmen['engine_requires_illustrated_groups']],
                ['Source has illustrated groups', $honmen['source_has_illustrated_groups'] ? 'yes' : 'no'],
                ['Mock exam ready', $honmen['mock_exam_ready'] ? 'yes' : 'no'],
            ],
        );

        foreach ($honmen['completeness_notes'] as $note) {
            $this->line(' - '.$note);
        }

        $this->newLine();
        $this->info('Source file audit');
        $source = $report['source_file_audit'];
        $this->table(
            ['Check', 'Value'],
            [
                ['Tuple storage source exists', $source['storage_source_exists'] ? 'yes' : 'no'],
                ['Object storage source exists', $source['storage_object_source_exists'] ? 'yes' : 'no'],
                ['Outside public/', $source['storage_outside_public'] ? 'yes' : 'no'],
                ['Tuple checksum', (string) ($source['tuple_checksum'] ?? 'n/a')],
                ['Object checksum', (string) ($source['object_checksum'] ?? 'n/a')],
                ['Full fixture gitignored', $source['fixture_full_html_gitignored'] ? 'yes' : 'no'],
                ['Git repository present', $source['git_repository_present'] ? 'yes' : 'no'],
            ],
        );

        $this->comment($report['disclaimer']);

        return self::SUCCESS;
    }
}
