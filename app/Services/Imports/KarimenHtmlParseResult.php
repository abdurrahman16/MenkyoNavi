<?php

namespace App\Services\Imports;

/**
 * @phpstan-type ParsedEntry array{
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
 *     source_metadata?: array<string, mixed>
 * }
 * @phpstan-type ParseError array{message: string, context: ?string}
 */
final class KarimenHtmlParseResult
{
    /**
     * @param  list<ParsedEntry>  $entries
     * @param  list<string>  $signKeys
     * @param  array<string, int>  $groupCounts
     * @param  list<ParseError>  $errors
     * @param  list<string>  $warnings
     */
    public function __construct(
        public readonly array $entries,
        public readonly array $signKeys,
        public readonly array $groupCounts,
        public readonly array $errors,
        public readonly array $warnings,
    ) {}

    public function entryCount(): int
    {
        return count($this->entries);
    }

    public function hasFatalErrors(): bool
    {
        return $this->errors !== [];
    }
}
