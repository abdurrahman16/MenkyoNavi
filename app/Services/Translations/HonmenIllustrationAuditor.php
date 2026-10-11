<?php

namespace App\Services\Translations;

use App\Models\QuestionImportEntry;
use Illuminate\Support\Collection;

/**
 * @phpstan-type HonmenAuditReport array{
 *     honmen_entry_count: int,
 *     honmen_group_counts: array<string, int>,
 *     entries_with_sign_key: int,
 *     unique_sign_keys: list<string>,
 *     illustrated_group_records_found: int,
 *     statements_per_group_evidence: int,
 *     engine_requires_standard: int,
 *     engine_requires_illustrated_groups: int,
 *     engine_requires_statements_per_group: int,
 *     source_has_illustrated_groups: bool,
 *     source_has_only_inline_sign_references: bool,
 *     completeness_notes: list<string>,
 *     mock_exam_ready: bool
 * }
 */
class HonmenIllustrationAuditor
{
    /**
     * @return HonmenAuditReport
     */
    public function audit(): array
    {
        /** @var Collection<int, QuestionImportEntry> $honmen */
        $honmen = QuestionImportEntry::query()
            ->where('exam_family', 'honmen')
            ->orderBy('source_group')
            ->orderBy('original_question_number')
            ->get();

        $groupCounts = $honmen
            ->groupBy('source_group')
            ->map(fn (Collection $group): int => $group->count())
            ->all();

        $withSigns = $honmen->filter(fn (QuestionImportEntry $entry): bool => filled($entry->sign_key));
        $signKeys = $withSigns
            ->pluck('sign_key')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $engineRequiresStandard = (int) config('exams.types.honmen.selection_rules.standard_true_false_count', 90);
        $engineRequiresIllustrated = (int) config('exams.types.honmen.selection_rules.illustrated_group_count', 5);
        $engineRequiresStatements = (int) config('exams.types.honmen.selection_rules.statements_per_illustrated_group', 3);

        // The HTML trainer stores ordinary T/F items, some with optional SVG sign keys.
        // It does not encode illustrated groups as 3-statement clusters.
        $illustratedGroupRecordsFound = 0;
        $statementsPerGroupEvidence = 0;

        $notes = [
            'Honmen source entries are ordinary True/False items (4 sets × 90).',
            'Sign keys / figure captions reference SVG illustrations for individual questions.',
            'No source structure was found for illustrated groups with 3 statements each.',
            'Do not convert ordinary sign questions into Honmen illustrated groups without clear source evidence.',
        ];

        $mockExamReady = $honmen->count() >= $engineRequiresStandard
            && $illustratedGroupRecordsFound >= $engineRequiresIllustrated;

        if (! $mockExamReady) {
            $notes[] = sprintf(
                'Full Honmen mock requirements are not met: need %d standard + %d illustrated groups (%d statements each); source provides %d standard T/F and %d illustrated groups.',
                $engineRequiresStandard,
                $engineRequiresIllustrated,
                $engineRequiresStatements,
                $honmen->count(),
                $illustratedGroupRecordsFound,
            );
        }

        return [
            'honmen_entry_count' => $honmen->count(),
            'honmen_group_counts' => $groupCounts,
            'entries_with_sign_key' => $withSigns->count(),
            'unique_sign_keys' => $signKeys,
            'illustrated_group_records_found' => $illustratedGroupRecordsFound,
            'statements_per_group_evidence' => $statementsPerGroupEvidence,
            'engine_requires_standard' => $engineRequiresStandard,
            'engine_requires_illustrated_groups' => $engineRequiresIllustrated,
            'engine_requires_statements_per_group' => $engineRequiresStatements,
            'source_has_illustrated_groups' => false,
            'source_has_only_inline_sign_references' => $withSigns->isNotEmpty(),
            'completeness_notes' => $notes,
            'mock_exam_ready' => $mockExamReady,
        ];
    }
}
