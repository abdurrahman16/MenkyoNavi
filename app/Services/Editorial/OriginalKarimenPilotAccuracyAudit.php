<?php

namespace App\Services\Editorial;

use App\Models\QuestionImportEntry;
use App\Models\QuestionImportEntryTranslation;
use RuntimeException;

/**
 * Task 44 research audit for the original Karimen pilot.
 *
 * Assessments never confer human accuracy, editorial, rights, or publication approval.
 */
class OriginalKarimenPilotAccuracyAudit
{
    public const AUDIT_RELATIVE_PATH = 'data/original_karimen_pilot_001_accuracy_audit.php';

    /**
     * @return array{
     *     collection_key: string,
     *     audited_at: string,
     *     law_version_note: string,
     *     sources_inspected: list<array<string, string>>,
     *     summary: array<string, int|string>,
     *     candidates: array<int, array<string, mixed>>
     * }
     */
    public function catalog(): array
    {
        $path = database_path(self::AUDIT_RELATIVE_PATH);

        if (! is_file($path)) {
            throw new RuntimeException('Original Karimen pilot accuracy audit catalog is missing: '.$path);
        }

        /** @var array<string, mixed> $catalog */
        $catalog = require $path;

        if (($catalog['collection_key'] ?? null) !== OriginalKarimenPilotStagingService::COLLECTION_KEY) {
            throw new RuntimeException('Accuracy audit collection_key mismatch.');
        }

        if (! is_array($catalog['candidates'] ?? null) || count($catalog['candidates']) !== 10) {
            throw new RuntimeException('Accuracy audit must cover exactly 10 candidates.');
        }

        return $catalog;
    }

    /**
     * @return array<string, mixed>
     */
    public function forNumber(int $number): array
    {
        $catalog = $this->catalog();
        $row = $catalog['candidates'][$number] ?? null;

        if (! is_array($row)) {
            throw new RuntimeException("Missing accuracy audit for pilot candidate #{$number}.");
        }

        return $row;
    }

    /**
     * @return list<string>
     */
    public function assessmentsThatBlockAutoPublication(): array
    {
        return [
            'ambiguous_needs_revision',
            'evidence_insufficient',
            'potentially_incorrect',
            'legal_effective_date_unresolved',
        ];
    }

    public function researchAssessmentConfersHumanApproval(string $assessment): bool
    {
        return false;
    }

    /**
     * Conservative overlap scan against imported staging rows (other source groups).
     *
     * @return list<array{candidate_id: string, overlap_type: string, other_stable_id: string}>
     */
    public function similarityFlags(): array
    {
        $pilot = app(OriginalKarimenPilotStagingService::class)->catalog();
        $flags = [];

        foreach ($pilot['candidates'] as $candidate) {
            $questionJa = trim((string) $candidate['question_ja']);
            if ($questionJa === '') {
                continue;
            }

            $exactTranslation = QuestionImportEntryTranslation::query()
                ->where('locale', 'ja')
                ->where('question_text', $questionJa)
                ->whereHas('entry', function ($query): void {
                    $query->where('source_group', '!=', OriginalKarimenPilotStagingService::SOURCE_GROUP);
                })
                ->with('entry:id,source_group,original_question_number')
                ->first();

            if ($exactTranslation !== null && $exactTranslation->entry !== null) {
                $flags[] = [
                    'candidate_id' => (string) $candidate['candidate_id'],
                    'overlap_type' => 'exact_japanese_translation_match',
                    'other_stable_id' => $exactTranslation->entry->stableSourceId(),
                ];
            }

            $exactEnglish = QuestionImportEntry::query()
                ->where('source_group', '!=', OriginalKarimenPilotStagingService::SOURCE_GROUP)
                ->where('english_question', $questionJa)
                ->first();

            if ($exactEnglish !== null) {
                $flags[] = [
                    'candidate_id' => (string) $candidate['candidate_id'],
                    'overlap_type' => 'exact_english_field_match',
                    'other_stable_id' => $exactEnglish->stableSourceId(),
                ];
            }
        }

        return $flags;
    }
}
