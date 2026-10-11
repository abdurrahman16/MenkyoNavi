<?php

namespace App\Services\Imports;

use App\Enums\QuestionImportDuplicateKind;
use App\Models\QuestionImportEntry;

/**
 * Compares a parsed import against itself and against already-staged entries.
 *
 * Never discards either record and never selects a winning answer key.
 */
class CrossSourceDuplicateAuditor
{
    public const SIMILARITY_THRESHOLD = 0.88;

    /**
     * @param  list<array{
     *     source_entry_checksum: string,
     *     content_fingerprint: string,
     *     question_text_fingerprint: string,
     *     english_question: string,
     *     english_explanation: string,
     *     correct_answer: bool,
     *     source_group: string
     * }>  $entries
     * @return array{
     *     exact_same_question_and_answer: int,
     *     exact_same_question_different_answer: int,
     *     same_question_different_explanation: int,
     *     similar_wording_needs_review: int,
     *     duplicates_inside_source: int,
     *     identical_across_sets: int,
     *     same_wording_different_answer: int,
     *     by_checksum: array<string, array{kind: string, of_checksum: ?string, of_entry_id: ?int}>
     * }
     */
    public function analyze(array $entries, ?string $excludeSourceKey = null): array
    {
        $byChecksum = [];

        foreach ($entries as $entry) {
            $byChecksum[$entry['source_entry_checksum']] = [
                'kind' => QuestionImportDuplicateKind::None->value,
                'of_checksum' => null,
                'of_entry_id' => null,
            ];
        }

        $inside = $this->analyzeInsideSource($entries, $byChecksum);

        $existingQuery = QuestionImportEntry::query()
            ->select([
                'id',
                'source_entry_checksum',
                'content_fingerprint',
                'question_text_fingerprint',
                'english_question',
                'english_explanation',
                'correct_answer',
                'source_group',
                'question_import_batch_id',
            ])
            ->with('batch:id,source_key');

        if ($excludeSourceKey !== null) {
            $existingQuery->whereHas('batch', function ($query) use ($excludeSourceKey): void {
                $query->where('source_key', '!=', $excludeSourceKey);
            });
        }

        $existing = $existingQuery->get();

        $exactSameQuestionAndAnswer = 0;
        $exactSameQuestionDifferentAnswer = 0;
        $sameQuestionDifferentExplanation = 0;
        $similarWording = 0;

        $existingByQuestionFp = $existing->groupBy('question_text_fingerprint');
        $existingByContentFp = $existing->groupBy('content_fingerprint');

        foreach ($entries as $entry) {
            $current = &$byChecksum[$entry['source_entry_checksum']];

            $contentMatches = $existingByContentFp->get($entry['content_fingerprint'], collect());
            if ($contentMatches->isNotEmpty()) {
                $match = $contentMatches->first();
                if ($this->shouldUpgrade($current['kind'], QuestionImportDuplicateKind::ExactCrossSourceMatch)) {
                    $exactSameQuestionAndAnswer++;
                    $current = [
                        'kind' => QuestionImportDuplicateKind::ExactCrossSourceMatch->value,
                        'of_checksum' => $match->source_entry_checksum,
                        'of_entry_id' => $match->id,
                    ];
                }

                continue;
            }

            $questionMatches = $existingByQuestionFp->get($entry['question_text_fingerprint'], collect());
            if ($questionMatches->isNotEmpty()) {
                $match = $questionMatches->first();
                $sameAnswer = (bool) $match->correct_answer === $entry['correct_answer'];
                $sameExplanation = $this->normalize($match->english_explanation) === $this->normalize($entry['english_explanation']);

                if (! $sameAnswer) {
                    if ($this->shouldUpgrade($current['kind'], QuestionImportDuplicateKind::ExactQuestionDifferentAnswer)) {
                        $exactSameQuestionDifferentAnswer++;
                        $current = [
                            'kind' => QuestionImportDuplicateKind::ExactQuestionDifferentAnswer->value,
                            'of_checksum' => $match->source_entry_checksum,
                            'of_entry_id' => $match->id,
                        ];
                    }

                    continue;
                }

                if (! $sameExplanation) {
                    if ($this->shouldUpgrade($current['kind'], QuestionImportDuplicateKind::SameQuestionDifferentExplanation)) {
                        $sameQuestionDifferentExplanation++;
                        $current = [
                            'kind' => QuestionImportDuplicateKind::SameQuestionDifferentExplanation->value,
                            'of_checksum' => $match->source_entry_checksum,
                            'of_entry_id' => $match->id,
                        ];
                    }

                    continue;
                }

                if ($this->shouldUpgrade($current['kind'], QuestionImportDuplicateKind::ExactCrossSourceMatch)) {
                    $exactSameQuestionAndAnswer++;
                    $current = [
                        'kind' => QuestionImportDuplicateKind::ExactCrossSourceMatch->value,
                        'of_checksum' => $match->source_entry_checksum,
                        'of_entry_id' => $match->id,
                    ];
                }

                continue;
            }

            if ($current['kind'] !== QuestionImportDuplicateKind::None->value) {
                continue;
            }

            foreach ($existing as $candidate) {
                if ($candidate->question_text_fingerprint === $entry['question_text_fingerprint']) {
                    continue;
                }

                $percent = 0.0;
                similar_text(
                    $this->normalize($entry['english_question']),
                    $this->normalize((string) $candidate->english_question),
                    $percent,
                );

                if (($percent / 100) < self::SIMILARITY_THRESHOLD) {
                    continue;
                }

                $similarWording++;
                $current = [
                    'kind' => QuestionImportDuplicateKind::SimilarWordingNeedsReview->value,
                    'of_checksum' => $candidate->source_entry_checksum,
                    'of_entry_id' => $candidate->id,
                ];

                break;
            }
        }

        unset($current);

        return [
            'exact_same_question_and_answer' => $exactSameQuestionAndAnswer,
            'exact_same_question_different_answer' => $exactSameQuestionDifferentAnswer,
            'same_question_different_explanation' => $sameQuestionDifferentExplanation,
            'similar_wording_needs_review' => $similarWording,
            'duplicates_inside_source' => $inside['duplicates_inside_source'],
            'identical_across_sets' => $inside['identical_across_sets'],
            'same_wording_different_answer' => $inside['same_wording_different_answer'],
            'by_checksum' => $byChecksum,
        ];
    }

    /**
     * @param  list<array{
     *     source_entry_checksum: string,
     *     content_fingerprint: string,
     *     question_text_fingerprint: string,
     *     correct_answer: bool,
     *     source_group: string
     * }>  $entries
     * @param  array<string, array{kind: string, of_checksum: ?string, of_entry_id: ?int}>  $byChecksum
     * @return array{
     *     duplicates_inside_source: int,
     *     identical_across_sets: int,
     *     same_wording_different_answer: int
     * }
     */
    private function analyzeInsideSource(array $entries, array &$byChecksum): array
    {
        $byContent = [];
        $byQuestionText = [];

        foreach ($entries as $entry) {
            $byContent[$entry['content_fingerprint']][] = $entry;
            $byQuestionText[$entry['question_text_fingerprint']][] = $entry;
        }

        $identicalAcrossSets = 0;
        $duplicatesInsideSource = 0;
        $sameWordingDifferentAnswer = 0;

        foreach ($byContent as $group) {
            if (count($group) < 2) {
                continue;
            }

            $groups = array_unique(array_column($group, 'source_group'));
            $canonical = $group[0];

            foreach (array_slice($group, 1) as $duplicate) {
                $duplicatesInsideSource++;

                if (count($groups) >= 2) {
                    $identicalAcrossSets++;
                    $kind = QuestionImportDuplicateKind::IdenticalAcrossSets;
                } else {
                    $kind = QuestionImportDuplicateKind::DuplicateInsideSource;
                }

                if ($this->shouldUpgrade($byChecksum[$duplicate['source_entry_checksum']]['kind'], $kind)) {
                    $byChecksum[$duplicate['source_entry_checksum']] = [
                        'kind' => $kind->value,
                        'of_checksum' => $canonical['source_entry_checksum'],
                        'of_entry_id' => null,
                    ];
                }
            }
        }

        foreach ($byQuestionText as $group) {
            if (count($group) < 2) {
                continue;
            }

            $answers = array_unique(array_map(
                static fn (array $entry): string => $entry['correct_answer'] ? '1' : '0',
                $group,
            ));

            if (count($answers) < 2) {
                continue;
            }

            $canonical = $group[0];

            foreach (array_slice($group, 1) as $duplicate) {
                if ($duplicate['correct_answer'] === $canonical['correct_answer']) {
                    continue;
                }

                $sameWordingDifferentAnswer++;

                if ($this->shouldUpgrade(
                    $byChecksum[$duplicate['source_entry_checksum']]['kind'],
                    QuestionImportDuplicateKind::SameWordingDifferentAnswer,
                )) {
                    $byChecksum[$duplicate['source_entry_checksum']] = [
                        'kind' => QuestionImportDuplicateKind::SameWordingDifferentAnswer->value,
                        'of_checksum' => $canonical['source_entry_checksum'],
                        'of_entry_id' => null,
                    ];
                }
            }
        }

        return [
            'duplicates_inside_source' => $duplicatesInsideSource,
            'identical_across_sets' => $identicalAcrossSets,
            'same_wording_different_answer' => $sameWordingDifferentAnswer,
        ];
    }

    private function shouldUpgrade(string $currentKind, QuestionImportDuplicateKind $candidate): bool
    {
        return $this->priority($candidate) > $this->priorityFromValue($currentKind);
    }

    private function priority(QuestionImportDuplicateKind $kind): int
    {
        return match ($kind) {
            QuestionImportDuplicateKind::None => 0,
            QuestionImportDuplicateKind::SameSourceReimport => 1,
            QuestionImportDuplicateKind::SimilarWordingNeedsReview => 2,
            QuestionImportDuplicateKind::DuplicateInsideSource => 3,
            QuestionImportDuplicateKind::IdenticalAcrossSets => 4,
            QuestionImportDuplicateKind::ExactCrossSourceMatch => 5,
            QuestionImportDuplicateKind::SameQuestionDifferentExplanation => 6,
            QuestionImportDuplicateKind::SameWordingDifferentAnswer => 7,
            QuestionImportDuplicateKind::ExactQuestionDifferentAnswer => 8,
        };
    }

    private function priorityFromValue(string $kind): int
    {
        $enum = QuestionImportDuplicateKind::tryFrom($kind);

        return $enum === null ? 0 : $this->priority($enum);
    }

    private function normalize(string $value): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($value));

        return is_string($normalized) ? $normalized : trim($value);
    }
}
