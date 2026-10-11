<?php

namespace App\Services\Editorial;

use App\Enums\AccuracyEvidenceStatus;
use App\Enums\BatchContentRightsStatus;
use App\Enums\FigureReviewStatus;
use App\Enums\QuestionImportMediaStatus;
use App\Models\QuestionImportBatch;
use App\Models\QuestionImportEntry;
use RuntimeException;

/**
 * Private, non-destructive statutory-evidence catalog for karimen_test_1 (Q1–Q50).
 *
 * Suggestions only — never confers answer-key verification or content-rights approval.
 */
class KarimenTest1EvidenceLedger
{
    public const SOURCE_GROUP = 'karimen_test_1';

    private const EVIDENCE_CHECKED_AT = '2026-10-10';

    private const URL_ROAD_TRAFFIC_ACT = 'https://laws.e-gov.go.jp/law/335AC0000000105';

    private const URL_ENFORCEMENT_REGULATIONS = 'https://laws.e-gov.go.jp/document?lawid=335M50000002060';

    private const URL_NPA_CROSSWALK = 'https://www.npa.go.jp/bureau/traffic/oudanhodou/info.html';

    private const URL_SAITAMA_LEARNER_SIGN = 'https://www.police.pref.saitama.lg.jp/f0130/menkyo/siteiigai-rojourensyuu.html';

    /**
     * @return array<string, mixed>
     */
    public function catalogFor(QuestionImportEntry $entry): array
    {
        $number = $this->resolveQuestionNumber($entry);
        $definition = $this->catalogDefinitions()[$number] ?? throw new RuntimeException(
            "Missing evidence catalog definition for karimen_test_1 Q{$number}."
        );

        $figureDependent = filled($entry->sign_key) || filled($entry->figure_caption);
        $figureReviewStatus = $this->resolveFigureReviewStatus($entry, $figureDependent);
        $figureReviewNotes = $definition['figure_review_notes'] ?? null;

        if ($figureDependent && $figureReviewStatus === FigureReviewStatus::FigureMissing) {
            $figureReviewNotes = trim(($figureReviewNotes ?? '').' Imported sign_key/figure_caption present; no verified media asset linked in staging.');
        }

        $row = [
            'source_stable_id' => $entry->stableSourceId(),
            'source_entry_id' => $entry->id,
            'original_question_number' => $number,
            'source_group' => self::SOURCE_GROUP,
            'legal_topic' => $definition['legal_topic'],
            'accuracy_evidence_status' => $definition['status']->value,
            'wording_ambiguity' => $definition['wording_ambiguity'],
            'figure_dependency' => $figureDependent,
            'figure_review_status' => $figureReviewStatus->value,
            'figure_review_notes' => $figureReviewNotes,
            'sources' => $definition['sources'],
            'publication_blockers_expected' => $this->publicationBlockersExpected($entry, $definition, $figureReviewStatus),
            'automated_only' => true,
            'confers_answer_key_approval' => false,
            'confers_content_rights_approval' => false,
        ];

        if (isset($definition['special_findings'])) {
            $row['special_findings'] = $definition['special_findings'];
        }

        return $row;
    }

    /**
     * @return array<string, mixed>
     */
    public function batchRightsSnapshot(?QuestionImportBatch $batch): array
    {
        $status = $batch?->content_rights_status instanceof BatchContentRightsStatus
            ? $batch->content_rights_status
            : BatchContentRightsStatus::tryFrom((string) ($batch?->content_rights_status ?? BatchContentRightsStatus::NoEvidenceSubmitted->value))
                ?? BatchContentRightsStatus::NoEvidenceSubmitted;

        return [
            'source_group' => self::SOURCE_GROUP,
            'question_import_batch_id' => $batch?->id,
            'source_filename' => $batch?->source_filename,
            'status' => $status->isCleared() ? 'cleared_by_human' : 'not_cleared',
            'content_rights_status' => $status->value,
            'content_rights_status_label' => app()->getLocale() === 'ja' ? $status->labelJa() : $status->label(),
            'claimed_creator' => $batch?->claimed_creator,
            'origin_url' => $batch?->origin_url,
            'permission_notes' => $batch?->permission_notes,
            'rights_document_original_name' => $batch?->rights_document_original_name,
            'content_rights_reviewed_at' => $batch?->content_rights_reviewed_at?->toIso8601String(),
            'claimed_author' => filled($batch?->claimed_creator) ? $batch->claimed_creator : 'unknown',
            'license' => filled($batch?->license_notes) ? 'recorded' : 'unknown',
            'written_permission' => filled($batch?->permission_notes) || filled($batch?->rights_document_path) ? 'recorded' : 'none',
            'note' => 'HTML possession and public web accessibility alone are insufficient for content-rights clearance; record explicit authorization before reuse.',
            'automated_only' => $batch?->content_rights_reviewed_by === null,
        ];
    }

    /**
     * Attribute patch for evidence ledger fields only (seed / admin tooling).
     *
     * @return array<string, mixed>
     */
    public function applyCollectedEvidence(QuestionImportEntry $entry): array
    {
        $catalog = $this->catalogFor($entry);

        return [
            'accuracy_evidence_status' => $catalog['accuracy_evidence_status'],
            'legal_topic' => $catalog['legal_topic'],
            'accuracy_evidence_sources' => $catalog['sources'],
            'accuracy_verification_notes' => $this->formatVerificationNotes($catalog),
            'figure_review_status' => $catalog['figure_review_status'],
            'figure_review_notes' => $catalog['figure_review_notes'],
        ];
    }

    private function resolveQuestionNumber(QuestionImportEntry $entry): int
    {
        if ($entry->source_group !== self::SOURCE_GROUP) {
            throw new RuntimeException(
                'KarimenTest1EvidenceLedger only applies to source_group '.self::SOURCE_GROUP.', got '.$entry->source_group.'.'
            );
        }

        $number = (int) $entry->original_question_number;

        if ($number < 1 || $number > 50) {
            throw new RuntimeException(
                'karimen_test_1 evidence catalog covers Q1–Q50 only; got Q'.$number.'.'
            );
        }

        return $number;
    }

    private function resolveFigureReviewStatus(
        QuestionImportEntry $entry,
        bool $figureDependent,
    ): FigureReviewStatus {
        if (! $figureDependent) {
            return FigureReviewStatus::NotApplicable;
        }

        if ($entry->media_availability === QuestionImportMediaStatus::Available) {
            return FigureReviewStatus::FigurePresentUnverified;
        }

        return FigureReviewStatus::FigureMissing;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array{summary: string, notes: list<string>}
     */
    private function publicationBlockersExpected(
        QuestionImportEntry $entry,
        array $definition,
        FigureReviewStatus $figureReviewStatus,
    ): array {
        $notes = [
            'Accuracy evidence remains below human_reviewed; answer_key_status stays verification_pending unless separately verified.',
            'content_rights_status remains unclear until batch authorization is recorded.',
        ];

        if ($definition['status'] !== AccuracyEvidenceStatus::HumanReviewed) {
            $notes[] = 'Automated accuracy_evidence_status suggestion: '.$definition['status']->value.' (not regulatory sign-off).';
        }

        if ($definition['wording_ambiguity']['flag'] ?? false) {
            $notes[] = 'Wording ambiguity flag: '.($definition['wording_ambiguity']['note'] ?? 'review import phrasing before translation.');
        }

        if ($figureReviewStatus->blocksPublicationReadiness()) {
            $notes[] = 'Figure/sign dependency unresolved ('.$figureReviewStatus->value.').';
        }

        if (in_array((int) $entry->original_question_number, [4, 23], true)) {
            $notes[] = 'Japanese draft intentionally withheld pending editorial clarification of English examination claim.';
        }

        if (($entry->review_flags ?? []) !== []) {
            $notes[] = 'Import review_flags present: '.implode(', ', $entry->review_flags);
        }

        return [
            'summary' => 'Expected publication blockers until human review, rights evidence, verified figures (if any), and answer-key verification complete.',
            'notes' => array_values(array_unique($notes)),
        ];
    }

    /**
     * @param  array<string, mixed>  $catalog
     */
    private function formatVerificationNotes(array $catalog): string
    {
        $lines = [
            'Private automated evidence catalog (2026-10-10). Does not verify answer key or approve rights.',
            'Suggested status: '.$catalog['accuracy_evidence_status'].'.',
        ];

        if ($catalog['wording_ambiguity']['flag'] ?? false) {
            $lines[] = 'Wording ambiguity: '.($catalog['wording_ambiguity']['note'] ?? 'flagged');
        }

        if (isset($catalog['special_findings'])) {
            $lines[] = 'Special findings: '.$catalog['special_findings'];
        }

        return implode("\n", $lines);
    }

    /**
     * @param  list<string>  $sections
     * @return list<array<string, mixed>>
     */
    private function sourcesFromSections(array $sections): array
    {
        $sources = [];

        foreach ($sections as $section) {
            $sources[] = $this->rtaSource($section['section'], $section['relationship'], $section['claim_supported'] ?? null, $section['uncertainty'] ?? null);
        }

        return $sources;
    }

    /**
     * @return array<string, mixed>
     */
    private function rtaSource(
        string $section,
        string $relationship,
        ?bool $claimSupported = null,
        ?string $uncertainty = null,
    ): array {
        return [
            'title' => 'Road Traffic Act (道路交通法)',
            'organization' => 'e-Gov (Japan)',
            'url' => self::URL_ROAD_TRAFFIC_ACT,
            'section' => $section,
            'checked_at' => self::EVIDENCE_CHECKED_AT,
            'claim_supported' => $claimSupported,
            'relationship' => $relationship,
            'uncertainty' => $uncertainty,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function enforcementSource(
        string $section,
        string $relationship,
        ?bool $claimSupported = null,
        ?string $uncertainty = null,
    ): array {
        return [
            'title' => 'Road Traffic Act Enforcement Regulations (道路交通法施行規則)',
            'organization' => 'e-Gov (Japan)',
            'url' => self::URL_ENFORCEMENT_REGULATIONS,
            'section' => $section,
            'checked_at' => self::EVIDENCE_CHECKED_AT,
            'claim_supported' => $claimSupported,
            'relationship' => $relationship,
            'uncertainty' => $uncertainty,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function topicOnlyRta(?string $uncertainty = null): array
    {
        return $this->rtaSource(
            'Statute index (topic anchor)',
            'topic_only',
            null,
            $uncertainty ?? 'Specific article not mapped in this automated pass; human reviewer should attach section-level citation.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function noAmbiguity(): array
    {
        return ['flag' => false, 'note' => null];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function catalogDefinitions(): array
    {
        return [
            1 => [
                'legal_topic' => 'One-way street lane position',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => $this->sourcesFromSections([
                    ['section' => 'Art. 17 (passing position on one-way roads)', 'relationship' => 'supports', 'claim_supported' => false],
                ]),
            ],
            2 => [
                'legal_topic' => 'Pedestrian/bicycle crossing sign meaning',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'figure_review_notes' => 'Sign illustration required to confirm ped/bike crossing plate variant.',
                'sources' => [
                    $this->enforcementSource('Regulatory sign categories (crossing-related)', 'topic_only', null, 'Exact sign code must match imported figure.'),
                    $this->rtaSource('Art. 2 / Art. 30 (crossing context)', 'topic_only', null, 'Figure determines whether statement covers pedestrian vs bicycle crossing designation.'),
                ],
            ],
            3 => [
                'legal_topic' => 'Overtaking general mopeds where overtaking prohibited',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => $this->sourcesFromSections([
                    ['section' => 'Art. 30 (overtaking prohibitions and exceptions)', 'relationship' => 'supports', 'claim_supported' => true],
                ]),
            ],
            4 => [
                'legal_topic' => 'Driver attitude / courtesy (non-statutory phrasing)',
                'status' => AccuracyEvidenceStatus::NeedsClarification,
                'wording_ambiguity' => [
                    'flag' => true,
                    'note' => 'Soft “give-and-take spirit” attitude wording is not a clear True/False regulatory claim; import flag ambiguous_terminology.',
                ],
                'sources' => [
                    $this->topicOnlyRta('No discrete RTA article maps cleanly to attitude-only examination wording; clarify intended testable rule before statutory citation.'),
                ],
                'special_findings' => 'needs_clarification — examination item reads as driving etiquette, not an enforceable RTA rule. Do not draft Japanese until editors confirm whether the item should remain True/False and which official terminology applies.',
            ],
            5 => [
                'legal_topic' => 'Yellow lane line crossing for turns',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => $this->sourcesFromSections([
                    ['section' => 'Art. 37 / lane-change rules with pavement markings', 'relationship' => 'topic_only', 'uncertainty' => 'Confirm marking type against enforcement regulations figure tables.'],
                ]),
            ],
            6 => [
                'legal_topic' => 'Road closed to specified vehicle classes (regulatory sign)',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'figure_review_notes' => 'Verify closed-to-vehicles sign matches imported caption.',
                'sources' => [
                    $this->enforcementSource('Regulatory signs — road closed to vehicles', 'topic_only', null, 'Figure must be matched to official sign catalog entry.'),
                ],
            ],
            7 => [
                'legal_topic' => 'Safe re-entry after overtaking',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => $this->sourcesFromSections([
                    ['section' => 'Art. 30 (overtaking and returning to lane)', 'relationship' => 'supports', 'claim_supported' => true],
                ]),
            ],
            8 => [
                'legal_topic' => 'Signalized intersection turn with auxiliary signal diagram',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'figure_review_notes' => 'Diagram labels (signals A/B) must be read from verified figure.',
                'sources' => [
                    $this->rtaSource('Art. 75–76 (signal meanings)', 'topic_only', null, 'Diagram-dependent; confirm arrow signal vs main signal layout.'),
                ],
            ],
            9 => [
                'legal_topic' => 'Braking distance definition',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => [
                    $this->topicOnlyRta('Technical driving-test definition; RTA uses related safe-stopping concepts but may not mirror this English glossary verbatim.'),
                ],
            ],
            10 => [
                'legal_topic' => 'Right-turn path illustration',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'figure_review_notes' => 'Turn path arrow must be confirmed against official diagram.',
                'sources' => [
                    $this->rtaSource('Art. 48 (method of turning)', 'topic_only', null, 'Figure shows correct/incorrect turn trace.'),
                ],
            ],
            11 => [
                'legal_topic' => 'Entering intersection when exit blocked',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => $this->sourcesFromSections([
                    ['section' => 'Art. 43 (prohibition on obstructing intersection)', 'relationship' => 'supports', 'claim_supported' => true],
                ]),
            ],
            12 => [
                'legal_topic' => 'Using side strip when road congested',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => $this->sourcesFromSections([
                    ['section' => 'Art. 12–13 (roadway vs side strip)', 'relationship' => 'supports', 'claim_supported' => false],
                ]),
            ],
            13 => [
                'legal_topic' => 'General moped right turn with regulatory sign',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'figure_review_notes' => 'Sign governs moped turn approach line.',
                'sources' => [
                    $this->rtaSource('Art. 48 (turning method)', 'topic_only', null, 'Combine with sign figure for moped-specific approach.'),
                ],
            ],
            14 => [
                'legal_topic' => 'Overtaking prohibition near crossings',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => [
                    $this->rtaSource('Art. 30-5 / Art. 30 (crossing overtaking limits)', 'supports', true),
                    [
                        'title' => 'Pedestrian crossing guidance',
                        'organization' => 'National Police Agency (Japan)',
                        'url' => self::URL_NPA_CROSSWALK,
                        'section' => 'Crossing safety overview',
                        'checked_at' => self::EVIDENCE_CHECKED_AT,
                        'claim_supported' => true,
                        'relationship' => 'topic_only',
                        'uncertainty' => 'NPA page supports context; distance measurement remains statutory.',
                    ],
                ],
            ],
            15 => [
                'legal_topic' => 'Wheelbase / turning path terminology',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => [
                    $this->topicOnlyRta('Vehicle dynamics term; confirm against official curriculum definition rather than RTA article alone.'),
                ],
            ],
            16 => [
                'legal_topic' => 'Correct driving posture',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => [
                    $this->topicOnlyRta('Operational skill standard; not a single RTA article statement.'),
                ],
            ],
            17 => [
                'legal_topic' => 'Horn use near vulnerable road users',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => $this->sourcesFromSections([
                    ['section' => 'Art. 70 (safe driving) / horn restrictions', 'relationship' => 'supports', 'claim_supported' => false],
                ]),
            ],
            18 => [
                'legal_topic' => 'Lane-change signaling sequence',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => $this->sourcesFromSections([
                    ['section' => 'Art. 46 (turn/lane-change signals)', 'relationship' => 'contradicts', 'claim_supported' => false, 'uncertainty' => 'Statute requires signal before change; “after mirror only” wording in item is suspect.'],
                ]),
            ],
            19 => [
                'legal_topic' => 'Learner / provisional practice identification plates',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => [
                    'flag' => true,
                    'note' => 'Separate terminology check: 仮免許練習中 vs 初心運転者 labels in translations must not be conflated.',
                ],
                'sources' => [
                    $this->rtaSource('Art. 87 para. 3 (display while practicing on learner permit)', 'supports', false),
                    $this->enforcementSource('Art. 15-3 / Art. 16 (learner driver sign placement)', 'supports', false),
                    [
                        'title' => 'Learner permit practice sign guidance',
                        'organization' => 'Saitama Prefectural Police',
                        'url' => self::URL_SAITAMA_LEARNER_SIGN,
                        'section' => '仮免許練習標識の取付け',
                        'checked_at' => self::EVIDENCE_CHECKED_AT,
                        'claim_supported' => false,
                        'relationship' => 'supports',
                        'uncertainty' => 'Prefectural guidance illustrates front and rear display; not a substitute for nationwide NPA citation.',
                    ],
                ],
                'special_findings' => 'evidence_collected — Art. 87(3) plus Enforcement Reg. 15-3/16 require display on front AND rear during 仮免許 practice; imported False answer rejects “front OR rear only”. Still not human_reviewed. Confirm 仮免許練習中 wording separately from 初心運転者 plate rules.',
            ],
            20 => [
                'legal_topic' => 'Overtaking prohibition at railway crossings',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => $this->sourcesFromSections([
                    ['section' => 'Art. 30 (railway crossing overtaking ban)', 'relationship' => 'supports', 'claim_supported' => true],
                ]),
            ],
            21 => [
                'legal_topic' => 'Steering preparedness near pedestrians/cyclists',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => $this->sourcesFromSections([
                    ['section' => 'Art. 70 (safe driving duty)', 'relationship' => 'supports', 'claim_supported' => true],
                ]),
            ],
            22 => [
                'legal_topic' => 'Left turn from lane with pavement marking',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'figure_review_notes' => 'Pavement arrow/marking dictates permitted movement.',
                'sources' => [
                    $this->enforcementSource('Pavement marking meanings', 'topic_only', null, 'Requires verified marking figure.'),
                ],
            ],
            23 => [
                'legal_topic' => 'Speed choice vs posted maximum for traffic flow',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => [
                    'flag' => true,
                    'note' => 'English couples “maximum speed limit” with smooth flow; explanation teaches safe speed — retain wording flag despite collected duty-of-care evidence.',
                ],
                'sources' => [
                    $this->rtaSource('Art. 70 (duty of safe driving / appropriate speed)', 'supports', false),
                ],
                'special_findings' => 'evidence_collected — Art. 70 safe-driving duty supports False (must not treat posted maximum as target speed for flow alone). Wording remains awkward; still not human_reviewed and Japanese draft withheld.',
            ],
            24 => [
                'legal_topic' => 'Default maximum speeds (automobile vs moped)',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => [
                    $this->rtaSource('Art. 29 (speed limits)', 'topic_only', null, 'Confirm vehicle class thresholds against current statutory km/h values.'),
                ],
            ],
            25 => [
                'legal_topic' => 'Pedestrian crossing yielding',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => [
                    $this->rtaSource('Art. 52 (pedestrian crossing)', 'supports', false),
                    [
                        'title' => 'Pedestrian crossing guidance',
                        'organization' => 'National Police Agency (Japan)',
                        'url' => self::URL_NPA_CROSSWALK,
                        'section' => 'Driver yielding at crossings',
                        'checked_at' => self::EVIDENCE_CHECKED_AT,
                        'claim_supported' => false,
                        'relationship' => 'topic_only',
                        'uncertainty' => null,
                    ],
                ],
            ],
            26 => [
                'legal_topic' => 'Roadway vs sidewalk use',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => $this->sourcesFromSections([
                    ['section' => 'Art. 12–13 (roadway and sidewalk)', 'relationship' => 'supports', 'claim_supported' => true],
                ]),
            ],
            27 => [
                'legal_topic' => 'Meaning of green signal / immediate start',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => $this->sourcesFromSections([
                    ['section' => 'Art. 75–76 (signal indications)', 'relationship' => 'supports', 'claim_supported' => false],
                ]),
            ],
            28 => [
                'legal_topic' => 'Deceleration on slow-speed road (sign)',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'figure_review_notes' => 'Confirm slow/最低速度-related sign matches caption.',
                'sources' => [
                    $this->enforcementSource('Regulatory signs — speed-related', 'topic_only', null, 'Figure required to confirm permitted speed band.'),
                ],
            ],
            29 => [
                'legal_topic' => 'Yielding to emergency vehicles',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => $this->sourcesFromSections([
                    ['section' => 'Art. 41 (emergency vehicle priority)', 'relationship' => 'supports', 'claim_supported' => true],
                ]),
            ],
            30 => [
                'legal_topic' => 'Seatbelt obligation',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => $this->sourcesFromSections([
                    ['section' => 'Art. 71-3 (seatbelt use)', 'relationship' => 'supports', 'claim_supported' => true],
                ]),
            ],
            31 => [
                'legal_topic' => 'Strong side-wind warning sign',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'figure_review_notes' => 'Warning sign artwork must be verified.',
                'sources' => [
                    $this->enforcementSource('Warning signs — wind', 'topic_only', null, 'Match figure to official warning sign code.'),
                ],
            ],
            32 => [
                'legal_topic' => 'Mobile phone / navigation distraction',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => $this->sourcesFromSections([
                    ['section' => 'Art. 71-3-2 / Art. 70 (attention while driving)', 'relationship' => 'supports', 'claim_supported' => false],
                ]),
            ],
            33 => [
                'legal_topic' => 'Intersection movement permission sign',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'figure_review_notes' => 'Sign dictates permitted movements for regular motor vehicles.',
                'sources' => [
                    $this->enforcementSource('Regulatory signs — designated movements', 'topic_only', null, 'Figure-dependent.'),
                ],
            ],
            34 => [
                'legal_topic' => 'Signal coordination assumption',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => $this->sourcesFromSections([
                    ['section' => 'Art. 75–76 (signal operation)', 'relationship' => 'supports', 'claim_supported' => false],
                ]),
            ],
            35 => [
                'legal_topic' => 'Hand signal — reversing',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'figure_review_notes' => 'Hand-signal diagram must be verified.',
                'sources' => [
                    $this->enforcementSource('Hand signal diagrams', 'topic_only', null, 'Confirm diagonal-down arm means reversing in official diagram.'),
                ],
            ],
            36 => [
                'legal_topic' => 'Overtaking approach distance',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => $this->sourcesFromSections([
                    ['section' => 'Art. 30 (safe overtaking)', 'relationship' => 'supports', 'claim_supported' => false],
                ]),
            ],
            37 => [
                'legal_topic' => 'Overtaking pass side (left vs right)',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => $this->sourcesFromSections([
                    ['section' => 'Art. 30 (overtaking on the left)', 'relationship' => 'supports', 'claim_supported' => false],
                ]),
            ],
            38 => [
                'legal_topic' => 'Slow driving (徐行) vs priority road / poor visibility',
                'status' => AccuracyEvidenceStatus::ConflictingEvidence,
                'wording_ambiguity' => [
                    'flag' => true,
                    'note' => 'English “right of way” may not align with statutory 優先道路 terminology; do not auto-verify.',
                ],
                'sources' => [
                    $this->rtaSource('Art. 36 para. 3 (priority-road vehicles excluded from certain 徐行 duties)', 'supports', false),
                    $this->rtaSource('Art. 42 (slow at places where traffic danger must be prevented — poor visibility intersections)', 'contradicts', null, 'May impose 徐行 even when driver believes they have priority.'),
                ],
                'special_findings' => 'conflicting_evidence — Art. 36(3) can support False for blanket 徐行 on priority-road vehicles, but Art. 42 still requires caution at poor-visibility uncontrolled intersections. English “right of way” ≠ 優先道路. Do NOT auto-verify answer key.',
            ],
            39 => [
                'legal_topic' => 'No U-turn regulatory sign',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'figure_review_notes' => 'Verify no-U-turn sign arrow orientation.',
                'sources' => [
                    $this->enforcementSource('Regulatory signs — turning prohibition', 'topic_only', null, 'Figure-dependent sign identification.'),
                ],
            ],
            40 => [
                'legal_topic' => 'Right turn vs oncoming straight/left traffic',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => $this->sourcesFromSections([
                    ['section' => 'Art. 48 / intersection yielding rules', 'relationship' => 'supports', 'claim_supported' => true],
                ]),
            ],
            41 => [
                'legal_topic' => 'Railway crossing stop and check',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => $this->sourcesFromSections([
                    ['section' => 'Art. 47 (railway crossing stopping and confirmation)', 'relationship' => 'supports', 'claim_supported' => true],
                ]),
            ],
            42 => [
                'legal_topic' => 'No-stopping zone pavement marking',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'figure_review_notes' => 'Marking pattern must be matched to official catalog.',
                'sources' => [
                    $this->enforcementSource('Pavement markings — stopping prohibition', 'topic_only', null, 'Figure-dependent.'),
                ],
            ],
            43 => [
                'legal_topic' => 'Unlicensed driving prohibition',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => $this->sourcesFromSections([
                    ['section' => 'Art. 64 (driving without license)', 'relationship' => 'supports', 'claim_supported' => true],
                ]),
            ],
            44 => [
                'legal_topic' => 'Lane-change signal timing (~3 seconds)',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => [
                    $this->topicOnlyRta('Timing rule typically taught in curriculum; confirm numeric “about 3 seconds” against official guidance.'),
                ],
            ],
            45 => [
                'legal_topic' => 'Police officer signal vs red light',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'figure_review_notes' => 'Officer lamp/hand signal diagram required.',
                'sources' => [
                    $this->rtaSource('Art. 77 (police officer traffic control)', 'topic_only', null, 'Figure shows side-to-side lamp meaning.'),
                ],
            ],
            46 => [
                'legal_topic' => 'Signaling when slowing or stopping',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => $this->sourcesFromSections([
                    ['section' => 'Art. 46 (deceleration/stop signals)', 'relationship' => 'supports', 'claim_supported' => false],
                ]),
            ],
            47 => [
                'legal_topic' => 'Sign vs pavement marking equivalence',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'figure_review_notes' => 'Both sign and marking figures must be verified together.',
                'sources' => [
                    $this->enforcementSource('Sign and marking correspondence tables', 'topic_only', null, 'Dual figure dependency.'),
                ],
            ],
            48 => [
                'legal_topic' => 'Lateral clearance when passing pedestrians',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => $this->sourcesFromSections([
                    ['section' => 'Art. 70 / passing pedestrians safely', 'relationship' => 'supports', 'claim_supported' => true],
                ]),
            ],
            49 => [
                'legal_topic' => 'Left-turn approach path',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => $this->sourcesFromSections([
                    ['section' => 'Art. 48 (left turn method)', 'relationship' => 'supports', 'claim_supported' => true],
                ]),
            ],
            50 => [
                'legal_topic' => 'Stopping for pedestrians at crossings',
                'status' => AccuracyEvidenceStatus::EvidenceCollected,
                'wording_ambiguity' => $this->noAmbiguity(),
                'sources' => [
                    $this->rtaSource('Art. 52 (pedestrian crossing)', 'supports', false),
                    [
                        'title' => 'Pedestrian crossing guidance',
                        'organization' => 'National Police Agency (Japan)',
                        'url' => self::URL_NPA_CROSSWALK,
                        'section' => 'Stopping for pedestrians',
                        'checked_at' => self::EVIDENCE_CHECKED_AT,
                        'claim_supported' => false,
                        'relationship' => 'topic_only',
                        'uncertainty' => 'Distinguish pedestrian already crossing vs about to cross.',
                    ],
                ],
            ],
        ];
    }
}
