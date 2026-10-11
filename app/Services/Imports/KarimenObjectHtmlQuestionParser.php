<?php

namespace App\Services\Imports;

use App\Contracts\HtmlQuestionBankParser;
use App\Enums\QuestionImportMediaStatus;
use RuntimeException;

/**
 * Restricted static extractor for the object-literal English HTML question bank.
 *
 * Source fields (from the HTML UI itself):
 * - q: English question text
 * - a: boolean True/False answer
 * - e: English explanation
 * - t: when truthy, UI marks the item as "trap-style" learning metadata
 *
 * Does not evaluate JavaScript or load the HTML document in a browser.
 */
class KarimenObjectHtmlQuestionParser implements HtmlQuestionBankParser
{
    public function __construct(
        private JsLiteralExtractor $literals = new JsLiteralExtractor,
    ) {}

    public function parseFile(string $absolutePath, ?string $fileChecksum = null): KarimenHtmlParseResult
    {
        if (! is_file($absolutePath)) {
            throw new RuntimeException("Import source HTML file was not found: {$absolutePath}");
        }

        $html = file_get_contents($absolutePath);

        if ($html === false) {
            throw new RuntimeException("Unable to read import source HTML file: {$absolutePath}");
        }

        return $this->parse($html, $fileChecksum ?? hash('sha256', $html));
    }

    public function parse(string $html, string $fileChecksum = ''): KarimenHtmlParseResult
    {
        /** @var list<array{message: string, context: ?string}> $errors */
        $errors = [];
        /** @var list<string> $warnings */
        $warnings = [];

        try {
            $testNames = $this->literals->extractConstArray($html, 'TESTNAMES');
            $chapters = $this->literals->extractConstArray($html, 'CH');
            $testSources = $this->literals->extractConstArray($html, 'TESTSRC');
        } catch (RuntimeException $exception) {
            $errors[] = ['message' => $exception->getMessage(), 'context' => 'TESTNAMES/CH/TESTSRC'];

            return new KarimenHtmlParseResult([], [], [], $errors, $warnings);
        }

        if (! is_array($testNames) || ! is_array($chapters) || ! is_array($testSources)) {
            $errors[] = [
                'message' => 'TESTNAMES, CH, and TESTSRC must be arrays.',
                'context' => 'TESTNAMES/CH/TESTSRC',
            ];

            return new KarimenHtmlParseResult([], [], [], $errors, $warnings);
        }

        try {
            $groups = $this->literals->extractPushArrays($html, 'TESTS');
        } catch (RuntimeException $exception) {
            $errors[] = ['message' => $exception->getMessage(), 'context' => 'TESTS.push'];

            return new KarimenHtmlParseResult([], [], [], $errors, $warnings);
        }

        if ($groups === []) {
            $errors[] = [
                'message' => 'No TESTS.push([...]) question groups were found.',
                'context' => 'TESTS',
            ];

            return new KarimenHtmlParseResult([], [], [], $errors, $warnings);
        }

        if (count($groups) !== count($testNames)) {
            $warnings[] = sprintf(
                'TESTS.push group count (%d) does not match TESTNAMES count (%d).',
                count($groups),
                count($testNames),
            );
        }

        $entries = [];
        $groupCounts = [];

        foreach ($groups as $index => $questions) {
            $testName = is_string($testNames[$index] ?? null) ? $testNames[$index] : 'unnamed_test_'.($index + 1);
            $chapter = is_string($chapters[$index] ?? null) ? $chapters[$index] : null;
            $testSource = is_string($testSources[$index] ?? null) ? $testSources[$index] : null;
            $sourceGroup = $this->makeSourceGroupKey($index + 1, $testName, $testSource);

            $groupEntries = $this->normalizeQuestionGroup(
                $questions,
                $sourceGroup,
                $testName,
                $chapter,
                $testSource,
                $fileChecksum,
                $errors,
                $warnings,
            );

            $entries = array_merge($entries, $groupEntries);
            $groupCounts[$sourceGroup] = count($groupEntries);
        }

        return new KarimenHtmlParseResult($entries, [], $groupCounts, $errors, $warnings);
    }

    private function makeSourceGroupKey(int $ordinal, string $testName, ?string $testSource): string
    {
        $prefix = $testSource !== null && trim($testSource) !== ''
            ? $this->slugify($testSource)
            : 'object_bank';

        return sprintf('%s_test_%d_%s', $prefix, $ordinal, $this->slugify($testName));
    }

    private function slugify(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? '';
        $normalized = trim($normalized, '_');

        return $normalized !== '' ? $normalized : 'group';
    }

    /**
     * @param  list<array{message: string, context: ?string}>  $errors
     * @param  list<string>  $warnings
     * @return list<array{
     *     source_group: string,
     *     exam_family: string,
     *     original_question_number: int,
     *     english_question: string,
     *     correct_answer: bool,
     *     english_explanation: string,
     *     sign_key: ?string,
     *     figure_caption: ?string,
     *     review_flags: list<string>,
     *     media_availability: string,
     *     source_entry_checksum: string,
     *     content_fingerprint: string,
     *     question_text_fingerprint: string,
     *     source_metadata: array<string, mixed>
     * }>
     */
    private function normalizeQuestionGroup(
        mixed $questions,
        string $sourceGroup,
        string $testName,
        ?string $chapter,
        ?string $testSource,
        string $fileChecksum,
        array &$errors,
        array &$warnings,
    ): array {
        if (! is_array($questions)) {
            $errors[] = [
                'message' => "Group {$sourceGroup} is not an array.",
                'context' => $sourceGroup,
            ];

            return [];
        }

        $entries = [];

        foreach (array_values($questions) as $index => $rawEntry) {
            $questionNumber = $index + 1;
            $context = "{$sourceGroup}#{$questionNumber}";

            if (! is_array($rawEntry)) {
                $errors[] = [
                    'message' => 'Malformed entry: expected an object with q/a/e fields.',
                    'context' => $context,
                ];

                continue;
            }

            $question = $rawEntry['q'] ?? null;
            $answer = $rawEntry['a'] ?? null;
            $explanation = $rawEntry['e'] ?? null;
            $trapStyle = array_key_exists('t', $rawEntry) ? $rawEntry['t'] : null;

            if (! is_string($question) || ! is_string($explanation)) {
                $errors[] = [
                    'message' => 'Malformed entry: q and e must be strings.',
                    'context' => $context,
                ];

                continue;
            }

            if (! is_bool($answer)) {
                $errors[] = [
                    'message' => 'Malformed entry: a must be a boolean true/false.',
                    'context' => $context,
                ];

                continue;
            }

            if ($trapStyle !== null && ! is_int($trapStyle) && ! is_bool($trapStyle) && ! is_float($trapStyle)) {
                $errors[] = [
                    'message' => 'Malformed entry: t must be numeric/boolean metadata when present.',
                    'context' => $context,
                ];

                continue;
            }

            $isTrapStyle = (bool) $trapStyle;
            $reviewFlags = $this->detectReviewFlags($question, $explanation, $isTrapStyle);

            $sourceMetadata = [
                'format' => 'object_literal_qate',
                'test_name' => $testName,
                'chapter_label' => $chapter,
                'test_source' => $testSource,
                'trap_style' => $isTrapStyle,
                'trap_style_raw' => $trapStyle,
                'field_map' => [
                    'q' => 'english_question',
                    'a' => 'correct_answer',
                    'e' => 'english_explanation',
                    't' => 'trap_style_learning_flag',
                ],
            ];

            $answerInt = $answer ? 1 : 0;

            $sourceEntryChecksum = hash('sha256', implode("\n", [
                $fileChecksum,
                $sourceGroup,
                (string) $questionNumber,
                $question,
                (string) $answerInt,
                $explanation,
                $isTrapStyle ? '1' : '0',
            ]));

            $contentFingerprint = hash('sha256', implode("\n", [
                $this->normalizeForFingerprint($question),
                (string) $answerInt,
                $this->normalizeForFingerprint($explanation),
            ]));

            $questionTextFingerprint = hash('sha256', $this->normalizeForFingerprint($question));

            $entries[] = [
                'source_group' => $sourceGroup,
                'exam_family' => 'unspecified',
                'original_question_number' => $questionNumber,
                'english_question' => $question,
                'correct_answer' => $answer,
                'english_explanation' => $explanation,
                'sign_key' => null,
                'figure_caption' => null,
                'review_flags' => $reviewFlags,
                'media_availability' => QuestionImportMediaStatus::None->value,
                'source_entry_checksum' => $sourceEntryChecksum,
                'content_fingerprint' => $contentFingerprint,
                'question_text_fingerprint' => $questionTextFingerprint,
                'source_metadata' => $sourceMetadata,
            ];
        }

        if ($entries === []) {
            $warnings[] = "Group {$sourceGroup} produced zero valid entries.";
        }

        return $entries;
    }

    /**
     * @return list<string>
     */
    private function detectReviewFlags(string $question, string $explanation, bool $isTrapStyle): array
    {
        $flags = ['exam_family_unclear'];

        if ($isTrapStyle) {
            $flags[] = 'trap_style_metadata';
        }

        if (trim($question) === '' || trim($explanation) === '') {
            $flags[] = 'empty_required_text';
        }

        if (preg_match('/\b(TODO|FIXME|XXX)\b/i', $question.$explanation) === 1) {
            $flags[] = 'placeholder_marker';
        }

        if (preg_match('/\?\?\?|\[unclear\]|\[sic\]/i', $question.$explanation) === 1) {
            $flags[] = 'explicit_uncertainty';
        }

        return array_values(array_unique($flags));
    }

    private function normalizeForFingerprint(string $value): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($value));

        return is_string($normalized) ? $normalized : trim($value);
    }
}
