<?php

namespace App\Services\Translations;

use App\Enums\StagedTranslationStatus;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportEntryTranslation;
use App\Support\SupportedLocale;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * @phpstan-type ImportSummary array{
 *     dry_run: bool,
 *     force_reviewed_overwrite: bool,
 *     row_count: int,
 *     created_count: int,
 *     updated_count: int,
 *     skipped_count: int,
 *     blocked_reviewed_count: int,
 *     duplicate_row_count: int,
 *     validation_error_count: int,
 *     quality_flag_count: int,
 *     errors: list<string>,
 *     english_mutations_blocked: int
 * }
 */
class StagedTranslationImportService
{
    public function __construct(
        private TranslationQualityChecker $qualityChecker,
    ) {}

    /**
     * @return ImportSummary
     */
    public function importFromFile(string $path, bool $dryRun = true, bool $forceReviewedOverwrite = false): array
    {
        if (! is_file($path)) {
            throw new RuntimeException('Translation import file was not found: '.$path);
        }

        $raw = file_get_contents($path);

        if ($raw === false) {
            throw new RuntimeException('Unable to read translation import file: '.$path);
        }

        if (! mb_check_encoding($raw, 'UTF-8')) {
            throw new RuntimeException('Translation import file is not valid UTF-8.');
        }

        try {
            /** @var array<string, mixed> $payload */
            $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new RuntimeException('Translation import JSON is invalid: '.$exception->getMessage(), 0, $exception);
        }

        return $this->importPayload($payload, $dryRun, $forceReviewedOverwrite);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return ImportSummary
     */
    public function importPayload(array $payload, bool $dryRun = true, bool $forceReviewedOverwrite = false): array
    {
        $formatVersion = (int) ($payload['format_version'] ?? 0);

        if ($formatVersion !== StagedTranslationExportService::FORMAT_VERSION) {
            throw new InvalidArgumentException('Unsupported translation import format_version.');
        }

        $rows = $payload['rows'] ?? null;

        if (! is_array($rows)) {
            throw new InvalidArgumentException('Translation import payload must include a rows array.');
        }

        $summary = [
            'dry_run' => $dryRun,
            'force_reviewed_overwrite' => $forceReviewedOverwrite,
            'row_count' => count($rows),
            'created_count' => 0,
            'updated_count' => 0,
            'skipped_count' => 0,
            'blocked_reviewed_count' => 0,
            'duplicate_row_count' => 0,
            'validation_error_count' => 0,
            'quality_flag_count' => 0,
            'errors' => [],
            'english_mutations_blocked' => 0,
        ];

        $seen = [];

        $process = function () use ($rows, $dryRun, $forceReviewedOverwrite, &$summary, &$seen): void {
            foreach ($rows as $index => $row) {
                if (! is_array($row)) {
                    $summary['validation_error_count']++;
                    $summary['errors'][] = "Row {$index}: expected an object.";

                    continue;
                }

                $locale = (string) ($row['locale'] ?? '');
                $sourceEntryId = (int) ($row['source_entry_id'] ?? 0);
                $stableId = (string) ($row['source_stable_id'] ?? '');
                $duplicateKey = $sourceEntryId.'|'.$locale;

                if (isset($seen[$duplicateKey])) {
                    $summary['duplicate_row_count']++;
                    $summary['errors'][] = "Row {$index}: duplicate translation row for {$stableId}/{$locale}.";

                    continue;
                }

                $seen[$duplicateKey] = true;

                if (! SupportedLocale::isSupported($locale)) {
                    $summary['validation_error_count']++;
                    $summary['errors'][] = "Row {$index}: unsupported locale [{$locale}].";

                    continue;
                }

                $locale = SupportedLocale::normalize($locale);

                $entry = QuestionImportEntry::query()->find($sourceEntryId);

                if ($entry === null && $stableId !== '') {
                    [$group, $number] = array_pad(explode(':', $stableId, 2), 2, null);
                    $entry = QuestionImportEntry::query()
                        ->where('source_group', $group)
                        ->where('original_question_number', (int) $number)
                        ->first();
                }

                if ($entry === null) {
                    $summary['validation_error_count']++;
                    $summary['errors'][] = "Row {$index}: staged entry not found ({$stableId}).";

                    continue;
                }

                if (isset($row['source_entry_checksum'])
                    && is_string($row['source_entry_checksum'])
                    && $row['source_entry_checksum'] !== ''
                    && ! hash_equals($entry->source_entry_checksum, $row['source_entry_checksum'])) {
                    $summary['validation_error_count']++;
                    $summary['errors'][] = "Row {$index}: source checksum mismatch for {$entry->stableSourceId()}.";

                    continue;
                }

                if ($this->attemptsEnglishSourceMutation($entry, $row)) {
                    $summary['english_mutations_blocked']++;
                    $summary['errors'][] = "Row {$index}: English source fields are read-only and were not changed.";
                }

                $questionText = $this->stringOrNull($row['question_text'] ?? null);
                $explanation = $this->stringOrNull($row['explanation'] ?? null);
                $figureCaption = $this->stringOrNull($row['figure_caption'] ?? null);
                $nuanceNote = $this->stringOrNull($row['nuance_note'] ?? null);

                if ($locale === 'en') {
                    $questionText = $entry->english_question;
                    $explanation = $entry->english_explanation;
                    $figureCaption = $entry->figure_caption;
                }

                $statusValue = (string) ($row['status'] ?? StagedTranslationStatus::Draft->value);

                try {
                    $status = StagedTranslationStatus::from($statusValue);
                } catch (\ValueError) {
                    $summary['validation_error_count']++;
                    $summary['errors'][] = "Row {$index}: invalid status [{$statusValue}].";

                    continue;
                }

                if ($status === StagedTranslationStatus::Reviewed
                    || $status === StagedTranslationStatus::ReadyForPublication
                    || $status === StagedTranslationStatus::RegulatoryVerificationPending) {
                    // Text existence alone must not imply human/regulatory review completion.
                    // Keep requested status only when explicitly supplied; quality flags still attach.
                } elseif ($questionText !== null && $questionText !== '' && $explanation !== null && $explanation !== '') {
                    if ($status === StagedTranslationStatus::PendingTranslation) {
                        $status = StagedTranslationStatus::Draft;
                    }
                }

                $qualityFlags = $this->qualityChecker->check($entry, [
                    'locale' => $locale,
                    'question_text' => $questionText,
                    'explanation' => $explanation,
                    'figure_caption' => $figureCaption,
                ]);

                if ($qualityFlags !== []) {
                    $summary['quality_flag_count']++;

                    if ($status === StagedTranslationStatus::Draft) {
                        $status = StagedTranslationStatus::NeedsReview;
                    }
                }

                $existing = QuestionImportEntryTranslation::query()
                    ->where('question_import_entry_id', $entry->id)
                    ->where('locale', $locale)
                    ->first();

                if ($existing !== null && $existing->status->isReviewedOrHigher() && ! $forceReviewedOverwrite) {
                    $summary['blocked_reviewed_count']++;
                    $summary['skipped_count']++;
                    $summary['errors'][] = "Row {$index}: reviewed translation protected ({$entry->stableSourceId()}/{$locale}). Use --force-reviewed to overwrite.";

                    continue;
                }

                if ($dryRun) {
                    if ($existing === null) {
                        $summary['created_count']++;
                    } else {
                        $summary['updated_count']++;
                    }

                    continue;
                }

                $attributes = [
                    'question_text' => $questionText,
                    'explanation' => $explanation,
                    'figure_caption' => $figureCaption,
                    'nuance_note' => $nuanceNote,
                    'status' => $status,
                    'quality_flags' => $qualityFlags,
                    'translated_at' => now(),
                    'reviewed_at' => $status->isReviewedOrHigher() ? now() : null,
                    'translator_note' => $this->stringOrNull($row['translator_note'] ?? null),
                ];

                if (array_key_exists('japanese_reading_data', $row)) {
                    $attributes['japanese_reading_data'] = $this->readingDataOrNull($row['japanese_reading_data']);
                }

                if ($existing === null) {
                    QuestionImportEntryTranslation::query()->create([
                        ...$attributes,
                        'question_import_entry_id' => $entry->id,
                        'locale' => $locale,
                    ]);
                    $summary['created_count']++;
                } else {
                    $existing->fill($attributes);
                    $existing->save();
                    $summary['updated_count']++;
                }
            }
        };

        if ($dryRun) {
            $process();
        } else {
            DB::transaction($process);
        }

        return $summary;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function attemptsEnglishSourceMutation(QuestionImportEntry $entry, array $row): bool
    {
        foreach ([
            'english_question' => $entry->english_question,
            'english_explanation' => $entry->english_explanation,
            'english_figure_caption' => $entry->figure_caption,
            'correct_answer' => $entry->correct_answer,
        ] as $field => $original) {
            if (! array_key_exists($field, $row)) {
                continue;
            }

            $incoming = $row[$field];

            if ($field === 'correct_answer') {
                if ((bool) $incoming !== (bool) $original) {
                    return true;
                }

                continue;
            }

            if ((string) ($incoming ?? '') !== (string) ($original ?? '')) {
                return true;
            }
        }

        return false;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            return null;
        }

        return $value;
    }

    /**
     * @return list<array{text: string, reading?: string|null}>|null
     */
    private function readingDataOrNull(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (! is_array($value)) {
            return null;
        }

        $segments = [];

        foreach ($value as $segment) {
            if (! is_array($segment)) {
                continue;
            }

            $text = trim((string) ($segment['text'] ?? ''));

            if ($text === '') {
                continue;
            }

            $reading = isset($segment['reading']) ? trim((string) $segment['reading']) : null;

            $segments[] = [
                'text' => $text,
                'reading' => $reading === '' ? null : $reading,
            ];
        }

        return $segments === [] ? null : $segments;
    }
}
