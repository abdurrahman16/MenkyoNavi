<?php

namespace App\Services\Imports;

use App\Contracts\HtmlQuestionBankParser;
use App\Enums\QuestionImportMediaStatus;
use RuntimeException;

/**
 * Restricted static extractor for MenkyoNavi English exam HTML sources (tuple format).
 *
 * Does not evaluate JavaScript or load the HTML document in a browser.
 */
class KarimenHtmlQuestionParser implements HtmlQuestionBankParser
{
    private const EXPECTED_ENTRY_COUNT = 610;

    /**
     * @var list<array{name: string, family: string, group: string}>
     */
    private const KARIMEN_GROUPS = [
        ['name' => 'TESTS', 'family' => 'karimen', 'group' => 'karimen_test_1'],
        ['name' => 'TESTS', 'family' => 'karimen', 'group' => 'karimen_test_2'],
        ['name' => 'TESTS', 'family' => 'karimen', 'group' => 'karimen_test_3'],
        ['name' => 'TESTS', 'family' => 'karimen', 'group' => 'karimen_test_4'],
        ['name' => 'TESTS', 'family' => 'karimen', 'group' => 'karimen_test_5'],
    ];

    /**
     * @var list<array{name: string, family: string, group: string}>
     */
    private const HONMEN_GROUPS = [
        ['name' => 'DL1', 'family' => 'honmen', 'group' => 'honmen_dl_1'],
        ['name' => 'DL2', 'family' => 'honmen', 'group' => 'honmen_dl_2'],
        ['name' => 'DL3', 'family' => 'honmen', 'group' => 'honmen_dl_3'],
        ['name' => 'DL4', 'family' => 'honmen', 'group' => 'honmen_dl_4'],
    ];

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

        $signKeys = $this->extractSignKeys($html);
        $signKeySet = array_fill_keys($signKeys, true);

        $entries = [];
        $groupCounts = [];

        try {
            $tests = $this->extractConstArray($html, 'TESTS');
        } catch (RuntimeException $exception) {
            $errors[] = ['message' => $exception->getMessage(), 'context' => 'TESTS'];

            return new KarimenHtmlParseResult([], $signKeys, [], $errors, $warnings);
        }

        if (! is_array($tests) || count($tests) !== 5) {
            $errors[] = [
                'message' => 'TESTS must contain exactly 5 nested test arrays.',
                'context' => 'TESTS',
            ];
        } else {
            foreach ($tests as $index => $testQuestions) {
                $meta = self::KARIMEN_GROUPS[$index];
                $groupEntries = $this->normalizeQuestionGroup(
                    $testQuestions,
                    $meta['group'],
                    $meta['family'],
                    $fileChecksum,
                    $signKeySet,
                    $errors,
                    $warnings,
                    expectedCount: 50,
                );
                $entries = array_merge($entries, $groupEntries);
                $groupCounts[$meta['group']] = count($groupEntries);
            }
        }

        foreach (self::HONMEN_GROUPS as $meta) {
            try {
                $questions = $this->extractConstArray($html, $meta['name']);
            } catch (RuntimeException $exception) {
                $errors[] = ['message' => $exception->getMessage(), 'context' => $meta['name']];
                $groupCounts[$meta['group']] = 0;

                continue;
            }

            $groupEntries = $this->normalizeQuestionGroup(
                $questions,
                $meta['group'],
                $meta['family'],
                $fileChecksum,
                $signKeySet,
                $errors,
                $warnings,
                expectedCount: 90,
            );
            $entries = array_merge($entries, $groupEntries);
            $groupCounts[$meta['group']] = count($groupEntries);
        }

        if (count($entries) !== self::EXPECTED_ENTRY_COUNT && $errors === []) {
            $warnings[] = sprintf(
                'Expected %d source entries but parsed %d.',
                self::EXPECTED_ENTRY_COUNT,
                count($entries),
            );
        }

        return new KarimenHtmlParseResult($entries, $signKeys, $groupCounts, $errors, $warnings);
    }

    /**
     * @return list<string>
     */
    public function extractSignKeys(string $html): array
    {
        $objectLiteral = $this->literals->extractConstObjectLiteral($html, 'SIGNS');
        preg_match_all('/^\s*([A-Za-z_][A-Za-z0-9_]*)\s*:/m', $objectLiteral, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    public function extractConstArray(string $html, string $name): mixed
    {
        return $this->literals->extractConstArray($html, $name);
    }

    /**
     * @param  array<string, true>  $signKeySet
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
        string $examFamily,
        string $fileChecksum,
        array $signKeySet,
        array &$errors,
        array &$warnings,
        int $expectedCount,
    ): array {
        if (! is_array($questions)) {
            $errors[] = [
                'message' => "Group {$sourceGroup} is not an array.",
                'context' => $sourceGroup,
            ];

            return [];
        }

        if (count($questions) !== $expectedCount) {
            $warnings[] = sprintf(
                'Group %s expected %d questions but found %d.',
                $sourceGroup,
                $expectedCount,
                count($questions),
            );
        }

        $entries = [];

        foreach (array_values($questions) as $index => $rawEntry) {
            $questionNumber = $index + 1;
            $context = "{$sourceGroup}#{$questionNumber}";

            if (! is_array($rawEntry)) {
                $errors[] = [
                    'message' => 'Malformed entry: expected an array tuple.',
                    'context' => $context,
                ];

                continue;
            }

            $tuple = array_values($rawEntry);
            $fieldCount = count($tuple);

            if ($fieldCount < 3 || $fieldCount > 5) {
                $errors[] = [
                    'message' => "Malformed entry: expected 3-5 fields, got {$fieldCount}.",
                    'context' => $context,
                ];

                continue;
            }

            $question = $tuple[0];
            $answer = $tuple[1];
            $explanation = $tuple[2];
            $signKey = $tuple[3] ?? null;
            $figureCaption = $tuple[4] ?? null;

            if (! is_string($question) || ! is_string($explanation)) {
                $errors[] = [
                    'message' => 'Malformed entry: question and explanation must be strings.',
                    'context' => $context,
                ];

                continue;
            }

            if (! is_int($answer) && ! (is_string($answer) && preg_match('/^[01]$/', $answer) === 1)) {
                $errors[] = [
                    'message' => 'Malformed entry: answer must be 0 or 1.',
                    'context' => $context,
                ];

                continue;
            }

            $answerInt = (int) $answer;

            if ($answerInt !== 0 && $answerInt !== 1) {
                $errors[] = [
                    'message' => 'Malformed entry: answer must be 0 or 1.',
                    'context' => $context,
                ];

                continue;
            }

            if ($signKey !== null && ! is_string($signKey)) {
                $errors[] = [
                    'message' => 'Malformed entry: sign key must be a string when present.',
                    'context' => $context,
                ];

                continue;
            }

            if ($figureCaption !== null && ! is_string($figureCaption)) {
                $errors[] = [
                    'message' => 'Malformed entry: figure caption must be a string when present.',
                    'context' => $context,
                ];

                continue;
            }

            $reviewFlags = $this->detectReviewFlags($question, $explanation, $signKey, $figureCaption);
            $mediaAvailability = $this->resolveMediaAvailability($signKey, $figureCaption, $signKeySet, $reviewFlags);

            if ($mediaAvailability === QuestionImportMediaStatus::MissingSignKey->value
                || $mediaAvailability === QuestionImportMediaStatus::NeedsReview->value) {
                $reviewFlags[] = 'media_review_required';
            }

            $reviewFlags = array_values(array_unique($reviewFlags));

            $sourceEntryChecksum = hash('sha256', implode("\n", [
                $fileChecksum,
                $sourceGroup,
                (string) $questionNumber,
                $question,
                (string) $answerInt,
                $explanation,
                (string) $signKey,
                (string) $figureCaption,
            ]));

            $contentFingerprint = hash('sha256', implode("\n", [
                $this->normalizeForFingerprint($question),
                (string) $answerInt,
                $this->normalizeForFingerprint($explanation),
            ]));

            $questionTextFingerprint = hash('sha256', $this->normalizeForFingerprint($question));

            $entries[] = [
                'source_group' => $sourceGroup,
                'exam_family' => $examFamily,
                'original_question_number' => $questionNumber,
                'english_question' => $question,
                'correct_answer' => $answerInt === 1,
                'english_explanation' => $explanation,
                'sign_key' => $signKey,
                'figure_caption' => $figureCaption,
                'review_flags' => $reviewFlags,
                'media_availability' => $mediaAvailability,
                'source_entry_checksum' => $sourceEntryChecksum,
                'content_fingerprint' => $contentFingerprint,
                'question_text_fingerprint' => $questionTextFingerprint,
                'source_metadata' => [
                    'format' => 'tuple_qaesf',
                    'field_map' => [
                        '0' => 'english_question',
                        '1' => 'correct_answer',
                        '2' => 'english_explanation',
                        '3' => 'sign_key',
                        '4' => 'figure_caption',
                    ],
                ],
            ];
        }

        return $entries;
    }

    /**
     * @return list<string>
     */
    private function detectReviewFlags(
        string $question,
        string $explanation,
        ?string $signKey,
        ?string $figureCaption,
    ): array {
        $flags = [];

        if (trim($question) === '' || trim($explanation) === '') {
            $flags[] = 'empty_required_text';
        }

        if (preg_match('/\b(TODO|FIXME|XXX)\b/i', $question.$explanation) === 1) {
            $flags[] = 'placeholder_marker';
        }

        if (preg_match('/\?\?\?|\[unclear\]|\[sic\]/i', $question.$explanation) === 1) {
            $flags[] = 'explicit_uncertainty';
        }

        if (preg_match('/\band\/or\b/i', $question) === 1) {
            $flags[] = 'ambiguous_terminology';
        }

        if (preg_match('/\b(advisable|it would be advisable)\b/i', $question) === 1) {
            $flags[] = 'soft_or_awkward_phrasing';
        }

        if ($signKey !== null && trim($signKey) === '') {
            $flags[] = 'blank_sign_key';
        }

        if ($figureCaption !== null && trim($figureCaption) === '') {
            $flags[] = 'blank_figure_caption';
        }

        if (($signKey === null) !== ($figureCaption === null)) {
            $flags[] = 'incomplete_illustration_pair';
        }

        return $flags;
    }

    /**
     * @param  array<string, true>  $signKeySet
     * @param  list<string>  $reviewFlags
     */
    private function resolveMediaAvailability(
        ?string $signKey,
        ?string $figureCaption,
        array $signKeySet,
        array &$reviewFlags,
    ): string {
        if ($signKey === null && $figureCaption === null) {
            return QuestionImportMediaStatus::None->value;
        }

        if ($signKey === null || trim($signKey) === '') {
            return QuestionImportMediaStatus::NeedsReview->value;
        }

        if (! isset($signKeySet[$signKey])) {
            $reviewFlags[] = 'unknown_sign_key';

            return QuestionImportMediaStatus::MissingSignKey->value;
        }

        return QuestionImportMediaStatus::Available->value;
    }

    private function normalizeForFingerprint(string $value): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($value));

        return is_string($normalized) ? $normalized : trim($value);
    }
}
