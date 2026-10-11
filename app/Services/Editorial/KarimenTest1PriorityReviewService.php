<?php

namespace App\Services\Editorial;

use App\Enums\AccuracyEvidenceStatus;
use App\Enums\AnswerKeyReviewStatus;
use App\Enums\BatchContentRightsStatus;
use App\Models\QuestionImportBatch;
use App\Models\QuestionImportEntry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Task 33 private research packets for Karimen Test 1 priority blockers.
 *
 * Recommendations only — never assigns human_reviewed, answer verification,
 * rights clearance, or publication.
 */
class KarimenTest1PriorityReviewService
{
    public const SOURCE_GROUP = 'karimen_test_1';

    /** @var list<int> */
    public const PRIORITY_QUESTION_NUMBERS = [4, 19, 23, 38];

    public function __construct(
        private KarimenTest1EvidenceLedger $ledger,
        private KarimenTest1SignAssetResolver $signs,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        /** @var Collection<int, QuestionImportEntry> $entries */
        $entries = QuestionImportEntry::query()
            ->where('source_group', self::SOURCE_GROUP)
            ->with(['batch', 'translations'])
            ->orderBy('original_question_number')
            ->get();

        if ($entries->count() !== 50) {
            throw new RuntimeException('Expected 50 karimen_test_1 entries, found '.$entries->count().'.');
        }

        $beforeChecksums = $entries->mapWithKeys(
            fn (QuestionImportEntry $entry): array => [$entry->id => $entry->source_entry_checksum]
        )->all();

        $priorityPackets = [];
        foreach (self::PRIORITY_QUESTION_NUMBERS as $number) {
            $entry = $entries->firstWhere('original_question_number', $number);
            if ($entry === null) {
                throw new RuntimeException("Missing karimen_test_1 Q{$number}.");
            }
            $priorityPackets[] = $this->buildPriorityPacket($entry);
        }

        $figureInventory = [];
        foreach ($entries as $entry) {
            if (! $entry->isFigureDependent()) {
                continue;
            }
            $figureInventory[] = $this->buildFigureInventoryRow($entry);
        }

        if (count($figureInventory) !== 14) {
            throw new RuntimeException('Expected 14 figure-dependent entries, found '.count($figureInventory).'.');
        }

        $batch = $entries->first()?->batch;
        $rights = $this->buildBatchRightsSection($batch, $entries);

        $afterChecksums = QuestionImportEntry::query()
            ->where('source_group', self::SOURCE_GROUP)
            ->orderBy('id')
            ->pluck('source_entry_checksum', 'id')
            ->all();

        if ($beforeChecksums !== $afterChecksums) {
            throw new RuntimeException('Priority review mutated source checksums — aborting.');
        }

        $publicationReady = $entries->filter(fn (QuestionImportEntry $e): bool => $e->isPublicationReady())->count();

        return [
            'format_version' => 1,
            'task' => 33,
            'generated_at' => now()->toIso8601String(),
            'private' => true,
            'source_group' => self::SOURCE_GROUP,
            'disclaimer' => [
                'research_is_not_human_approval' => true,
                'does_not_set_human_reviewed' => true,
                'does_not_verify_answer_keys' => true,
                'does_not_clear_rights' => true,
                'does_not_publish' => true,
                'imported_source_immutable' => true,
            ],
            'section_a_priority_questions' => $priorityPackets,
            'section_b_figure_inventory' => $figureInventory,
            'section_c_source_rights_review' => $rights,
            'section_d_proposed_corrections' => $this->collectProposedCorrections($priorityPackets),
            'section_e_remaining_blockers' => [
                'publication_ready_count' => $publicationReady,
                'blocked_count' => 50 - $publicationReady,
                'human_decisions_required' => [
                    'Decide whether Q4 remains an attitude item or is rewritten/excluded; keep withheld until clarified.',
                    'Decide whether Q23 English should be rewritten to match Art. 70 safe-speed teaching before translating.',
                    'Decide whether Q38 “right of way” means statutory 優先道路; if not, Art. 42 may require 徐行 — keep conflicting_evidence until clarified.',
                    'Confirm Q19 “learner driver” wording maps to 仮免許練習中 (Art. 87(3)), not 初心運転者標章.',
                    'Editorially verify each of the 14 inline SIGNS against question meaning and Japanese left-side geometry.',
                    'Obtain and record batch reproduction rights / permission documents before any publication.',
                ],
                'gates_still_enforced' => [
                    'content_rights not approved_for_public_reuse',
                    'answer_key not verified',
                    'accuracy_evidence not human_reviewed',
                    'figure_review not reviewed_sufficient when figure-dependent',
                    'import_review_flags present (Q4, Q23)',
                    'japanese ready_for_publication missing for untranslated / unreviewed drafts',
                ],
            ],
            'integrity_check' => [
                'source_checksums_unchanged' => true,
                'published_count' => $entries->where('is_published', true)->count(),
                'human_reviewed_accuracy_count' => $entries
                    ->where('accuracy_evidence_status', AccuracyEvidenceStatus::HumanReviewed)
                    ->count(),
                'verified_answer_key_count' => $entries
                    ->where('answer_key_status', AnswerKeyReviewStatus::Verified)
                    ->count(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array{json_path: string, markdown_path: string}
     */
    public function writePrivateReports(array $report, ?string $jsonPath = null, ?string $markdownPath = null): array
    {
        $jsonPath ??= storage_path('app/menkyo-exports/karimen_test_1_priority_review.json');
        $markdownPath ??= storage_path('app/menkyo-exports/karimen_test_1_priority_review.md');

        $this->assertPrivateExportPath($jsonPath);
        $this->assertPrivateExportPath($markdownPath);

        File::ensureDirectoryExists(dirname($jsonPath));
        File::put($jsonPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        File::put($markdownPath, $this->toMarkdown($report));

        return [
            'json_path' => $jsonPath,
            'markdown_path' => $markdownPath,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPriorityPacket(QuestionImportEntry $entry): array
    {
        $number = (int) $entry->original_question_number;
        $catalog = $this->ledger->catalogFor($entry);

        $packet = [
            'original_question_number' => $number,
            'source_entry_id' => $entry->id,
            'source_stable_id' => $entry->stableSourceId(),
            'imported' => [
                'english_question' => $entry->english_question,
                'correct_answer' => (bool) $entry->correct_answer,
                'english_explanation' => $entry->english_explanation,
                'sign_key' => $entry->sign_key,
                'figure_caption' => $entry->figure_caption,
                'source_entry_checksum' => $entry->source_entry_checksum,
            ],
            'historical_flags' => [
                'review_flags' => $entry->review_flags ?? [],
                'accuracy_evidence_status' => $entry->accuracy_evidence_status->value,
                'answer_key_status' => $entry->answer_key_status->value,
                'content_rights_status' => $entry->content_rights_status->value,
                'figure_review_status' => $entry->figure_review_status->value,
                'translation_locale_count' => $entry->translations->filter(fn ($t) => $t->hasCompleteText())->count(),
            ],
            'legal_topic' => $catalog['legal_topic'],
            'supporting_legal_evidence' => [],
            'contradictory_evidence' => [],
            'unresolved_ambiguity' => '',
            'proposed_editorial_action' => '',
            'proposed_correction' => null,
            'confidence_and_limitations' => '',
            'human_reviewer_still_must_decide' => [],
            'layers' => [
                'imported_source_claim' => null,
                'primary_legal_source_text' => null,
                'research_interpretation' => null,
                'proposed_editorial_correction' => null,
                'actual_human_approval' => 'none — this packet does not grant approval',
            ],
            'catalog_special_findings' => $catalog['special_findings'] ?? null,
        ];

        return match ($number) {
            4 => $this->enrichQ4($packet, $entry),
            19 => $this->enrichQ19($packet, $entry),
            23 => $this->enrichQ23($packet, $entry),
            38 => $this->enrichQ38($packet, $entry),
            default => $packet,
        };
    }

    /**
     * @param  array<string, mixed>  $packet
     * @return array<string, mixed>
     */
    private function enrichQ4(array $packet, QuestionImportEntry $entry): array
    {
        $packet['supporting_legal_evidence'] = [
            $this->source(
                '道路交通法',
                'e-Gov 法令検索（デジタル庁）',
                'https://laws.e-gov.go.jp/law/335AC0000000105',
                '一般規定・安全運転義務（第70条）等は存在するが、「give-and-take spirit」自体を定義する条文ではない',
                'Topic-only: Japanese RTA contains concrete duties (e.g. Art. 70) but not an attitude slogan matching the English statement.',
                'topic_only',
                'No discrete article makes the English True/False statement a clear statutory claim.'
            ),
        ];
        $packet['contradictory_evidence'] = [];
        $packet['unresolved_ambiguity'] = 'English teaches a soft courtesy attitude (“give-and-take spirit”) rather than a falsifiable regulatory condition. Import flag ambiguous_terminology. No figure. Explanation only restates “attitude required,” providing no operational rule.';
        $packet['proposed_editorial_action'] = 'Keep Q4 withheld from translation and publication until editors decide: (a) rewrite into a concrete RTA-backed statement, (b) reclassify as non-exam educational copy, or (c) permanently exclude from the scored bank.';
        $packet['proposed_correction'] = [
            'status' => 'proposal_only_not_applied',
            'keep_imported_answer_unchanged' => true,
            'imported_answer' => (bool) $entry->correct_answer,
            'proposed_clarified_statement_en' => null,
            'reason_no_clarified_statement' => 'Meaning cannot be established as a single True/False regulatory claim without inventing a different examination item.',
            'recommend_withhold' => true,
            'translation_impact' => 'Do not invent JA/other locale drafts until source intent is clarified.',
        ];
        $packet['confidence_and_limitations'] = 'High confidence that the ambiguity is wording/category, not a missing figure. Low confidence that any automatic rewrite would match the original item author’s intent.';
        $packet['human_reviewer_still_must_decide'] = [
            'Whether an attitude item belongs in a scored True/False bank.',
            'Whether to rewrite to a concrete duty (e.g. pedestrian protection) or exclude.',
            'Whether to clear or retain import_review_flags after rewrite.',
        ];
        $packet['layers']['imported_source_claim'] = 'Drivers must have a give-and-take spirit and respect others (True).';
        $packet['layers']['primary_legal_source_text'] = 'No matching discrete statutory slogan located on e-Gov RTA (accessed 2026-10-10).';
        $packet['layers']['research_interpretation'] = 'Ambiguous English etiquette wording; not suitable for automated approval.';
        $packet['layers']['proposed_editorial_correction'] = 'Withhold; no rewritten statement applied.';

        return $packet;
    }

    /**
     * @param  array<string, mixed>  $packet
     * @return array<string, mixed>
     */
    private function enrichQ19(array $packet, QuestionImportEntry $entry): array
    {
        $packet['supporting_legal_evidence'] = [
            $this->source(
                '道路交通法',
                'e-Gov 法令検索（デジタル庁）',
                'https://laws.e-gov.go.jp/law/335AC0000000105',
                '第87条第3項（仮免許による練習時の標識）',
                'Practice driving on a provisional license requires the Cabinet Order-prescribed plate on the front AND rear of the vehicle.',
                'supports',
                'Applies to provisional-license (仮免許) practice, not every learner category and not 初心運転者標章.'
            ),
            $this->source(
                '道路交通法施行規則',
                'e-Gov 法令検索（デジタル庁）',
                'https://laws.e-gov.go.jp/document?lawid=335M50000002060',
                '第15条の3・第16条（練習運転のための標識の表示・様式）',
                'Display height/visibility rules and prescribed plate style for provisional-license practice signs.',
                'supports',
                'Does not authorize English “learner driver” as an official translation of 仮免許練習中.'
            ),
            $this->source(
                '仮運転免許証での路上練習について',
                '埼玉県警察',
                'https://www.police.pref.saitama.lg.jp/f0130/menkyo/siteiigai-rojourensyuu.html',
                '練習のための標識（前後）',
                'Prefectural guidance: attach the practice plate on both front and rear at specified heights.',
                'supports',
                'Prefectural educational page, not the statute itself; consistent with Art. 87(3).'
            ),
        ];
        $packet['contradictory_evidence'] = [];
        $packet['unresolved_ambiguity'] = 'English says “learner driver” sign and “front OR rear.” Japanese law for 仮免許練習 requires BOTH front and rear. Separately, 初心運転者標章 (beginner mark) is a different obligation — terminology must not be mixed.';
        $packet['proposed_editorial_action'] = 'Retain imported False pending human review; record that Art. 87(3) supports “not only front OR rear.” Clarify educational English to “provisional-license practice plate (仮免許練習中)” before advancing translations.';
        $packet['proposed_correction'] = [
            'status' => 'proposal_only_not_applied',
            'keep_imported_answer_unchanged' => true,
            'imported_answer' => false,
            'proposed_answer' => false,
            'proposed_clarified_statement_en' => 'When a vehicle is driven for practice under a provisional license (仮免許), the prescribed “仮免許練習中” plate must be displayed on both the front and the rear of the vehicle.',
            'what_changed' => 'Replaces ambiguous “learner driver” / OR with provisional-license practice plate / BOTH, aligning with Art. 87(3).',
            'translation_impact' => 'Existing eight-locale drafts should be re-checked for 仮免許練習中 vs 初心運転者 confusion after human approval of English clarification.',
        ];
        $packet['confidence_and_limitations'] = 'High confidence on front-and-rear requirement for 仮免許練習. Medium confidence that the imported item intended 仮免許 rather than another learner mark — English is imprecise.';
        $packet['human_reviewer_still_must_decide'] = [
            'Confirm the item targets 仮免許練習中, not 初心運転者標章.',
            'Whether to adopt the clarified English proposal without changing the imported False answer.',
            'Whether JA/other drafts need terminology correction after clarification.',
        ];
        $packet['layers']['imported_source_claim'] = 'Plate on front OR rear is enough (statement); imported answer False.';
        $packet['layers']['primary_legal_source_text'] = 'RTA Art. 87(3): front and rear; Enforcement Reg. 15-3/16 (accessed 2026-10-10).';
        $packet['layers']['research_interpretation'] = 'Imported False is consistent with BOTH-sides rule if the topic is provisional-license practice plates.';
        $packet['layers']['proposed_editorial_correction'] = 'Clarified EN statement proposed; not written into DB source fields.';

        return $packet;
    }

    /**
     * @param  array<string, mixed>  $packet
     * @return array<string, mixed>
     */
    private function enrichQ23(array $packet, QuestionImportEntry $entry): array
    {
        $packet['supporting_legal_evidence'] = [
            $this->source(
                '道路交通法',
                'e-Gov 法令検索（デジタル庁）',
                'https://laws.e-gov.go.jp/law/335AC0000000105',
                '第70条（安全運転の義務）',
                'Drivers must operate at a speed and in a manner that does not endanger others, according to road, traffic, and vehicle conditions.',
                'supports',
                'Supports rejecting “proceed at the maximum speed limit to make traffic flow smoothly” as advice.'
            ),
        ];
        $packet['contradictory_evidence'] = [];
        $packet['unresolved_ambiguity'] = 'Import flag soft_or_awkward_phrasing: statement mixes “advisable” with “maximum speed limit” and traffic-flow goal. Explanation correctly teaches safe speed within the limit. Ambiguity is English quality, not missing figure.';
        $packet['proposed_editorial_action'] = 'Keep withheld from new translations until English is clarified; imported False may remain if rewritten to deny treating the posted maximum as a target speed.';
        $packet['proposed_correction'] = [
            'status' => 'proposal_only_not_applied',
            'keep_imported_answer_unchanged' => true,
            'imported_answer' => false,
            'proposed_answer' => false,
            'proposed_clarified_statement_en' => 'Automobiles should travel at the posted maximum speed whenever possible in order to keep traffic flowing smoothly.',
            'what_changed' => 'Removes “advisable” softener and states the false claim more clearly so False remains pedagogically sharp against Art. 70.',
            'alternate_true_statement_en' => 'Automobiles must travel at a safe speed suited to road, traffic, and weather conditions, and must not exceed the legal speed limit.',
            'translation_impact' => 'Do not generate new locale drafts from unresolved soft English; after clarification, re-export translation packages.',
        ];
        $packet['confidence_and_limitations'] = 'High confidence Art. 70 supports the imported False teaching. Medium confidence about the best clarified English wording for exam style.';
        $packet['human_reviewer_still_must_decide'] = [
            'Adopt clarified false-claim wording vs rewrite as a True safe-speed statement.',
            'Clear soft_or_awkward_phrasing flag after rewrite.',
            'Whether existing translation stubs (none currently) may proceed.',
        ];
        $packet['layers']['imported_source_claim'] = 'It is advisable to proceed at the maximum speed limit for smooth flow (False).';
        $packet['layers']['primary_legal_source_text'] = 'RTA Art. 70 safe-driving duty (accessed 2026-10-10).';
        $packet['layers']['research_interpretation'] = 'Legal duty of care contradicts maximizing posted speed for flow alone.';
        $packet['layers']['proposed_editorial_correction'] = 'Clarified EN false-claim / alternate True statement proposed; not applied to source.';

        return $packet;
    }

    /**
     * @param  array<string, mixed>  $packet
     * @return array<string, mixed>
     */
    private function enrichQ38(array $packet, QuestionImportEntry $entry): array
    {
        $packet['supporting_legal_evidence'] = [
            $this->source(
                '道路交通法',
                'e-Gov 法令検索（デジタル庁）',
                'https://laws.e-gov.go.jp/law/335AC0000000105',
                '第36条第3項',
                'At uncontrolled intersections, vehicles other than those on a priority road (優先道路) must slow (徐行) when the crossing road is a priority road or clearly wider.',
                'supports',
                'Supports False only if “right of way” means statutory 優先道路 — not mere left-hand approach priority.'
            ),
            $this->source(
                '道路交通法',
                'e-Gov 法令検索（デジタル庁）',
                'https://laws.e-gov.go.jp/law/335AC0000000105',
                '第42条第1号',
                '徐行 is required when entering/passing a poor left-right visibility intersection, EXCEPT when traffic is controlled OR the vehicle is traveling on a priority road (優先道路).',
                'supports',
                'Art. 42(1) parentheses also exclude 優先道路 travelers from that visibility-based 徐行 duty.'
            ),
        ];
        $packet['contradictory_evidence'] = [
            $this->source(
                '道路交通法',
                'e-Gov 法令検索（デジタル庁）',
                'https://laws.e-gov.go.jp/law/335AC0000000105',
                '第36条第1項・第2項と「明らかに広い道路」',
                'Left-side approach priority and “clearly wider road” duties are not identical to 優先道路. Art. 42(1) exemption is for 優先道路, not for every vehicle that “has right of way.”',
                'contradicts',
                'If English “right of way” means left-hand priority or wider-road priority only, poor-visibility 徐行 under Art. 42 may still apply — undermining a blanket False.'
            ),
        ];
        $packet['article_comparison'] = [
            [
                'provision' => 'Art. 36(3)',
                'situation' => 'Entering uncontrolled intersection when crossing road is priority road or clearly wider',
                'conditions' => 'No traffic control; crossing road priority/wider',
                'exceptions' => 'Vehicles already on a priority road are excluded from this 徐行 duty',
                'relation_to_q38' => 'Supports “not always necessary to slow” only for statutory 優先道路 travelers facing that Art. 36(3) trigger',
            ],
            [
                'provision' => 'Art. 42(1)',
                'situation' => 'Entering/passing intersection with poor left-right visibility',
                'conditions' => 'Poor visibility left/right',
                'exceptions' => 'Traffic control at the intersection OR traveling on a priority road (優先道路)',
                'relation_to_q38' => 'English mentions poor visibility — Art. 42 is directly on-point; exemption again requires 優先道路, not vague “right of way”',
            ],
            [
                'provision' => 'Art. 36(1)/(2) left / wider road',
                'situation' => 'Uncontrolled intersection priority among vehicles',
                'conditions' => 'No Art. 36(2) priority-road/wider trigger for (1); wider/priority for (2)',
                'exceptions' => 'Separate from Art. 42 visibility 徐行 exemption wording',
                'relation_to_q38' => 'Having ordinary intersection priority ≠ automatic Art. 42 exemption',
            ],
        ];
        $packet['unresolved_ambiguity'] = 'English “vehicles WITH right of way” is not equivalent to Japanese 優先道路. Explanation says slowing is “not always necessary” on a road with right of way. If reviewers read “right of way” as 優先道路, imported False is supported by Art. 36(3) and Art. 42(1) exceptions. If they read it as any preferential approach (e.g. left-hand rule), Art. 42 may still require 徐行 at poor visibility. Preserve conflicting_evidence.';
        $packet['proposed_editorial_action'] = 'Do not change the imported False answer. Propose clarified English that uses “priority road (優先道路)” and keep status conflicting_evidence / research pending until a human decides terminology.';
        $packet['proposed_correction'] = [
            'status' => 'proposal_only_not_applied',
            'keep_imported_answer_unchanged' => true,
            'imported_answer' => false,
            'proposed_answer' => false,
            'proposed_clarified_statement_en' => 'At an uncontrolled intersection with poor left-right visibility, a vehicle traveling on a legally designated priority road (優先道路) is not always required to slow to 徐行 solely because visibility is poor.',
            'what_changed' => 'Replaces “right of way” with statutory 優先道路 and ties the exception to Art. 42(1)/36(3) wording.',
            'effect_on_true_false' => 'Keeps False for the over-broad claim that every vehicle with “right of way” must slow; still requires human confirmation that this was the intended exam claim.',
            'translation_impact' => 'Existing eight-locale drafts must be checked for 優先道路 vs 優先/右方/左方 confusions after human approval.',
        ];
        $packet['confidence_and_limitations'] = 'High confidence on the statutory texts of Arts. 36(3) and 42(1) (e-Gov accessed 2026-10-10). Low-to-medium confidence that the imported English intended 優先道路. Conflict preserved deliberately.';
        $packet['human_reviewer_still_must_decide'] = [
            'Whether “right of way” in the imported English means 優先道路.',
            'Whether to adopt the clarified EN proposal and then verify the imported False.',
            'Whether JA drafts already overstate/understate the exception.',
        ];
        $packet['layers']['imported_source_claim'] = 'At poor-visibility uncontrolled intersections, vehicles with right of way must slow (statement); imported answer False.';
        $packet['layers']['primary_legal_source_text'] = 'Art. 36(3) and Art. 42(1) both carve out 優先道路; Art. 42 still binds non-priority-road traffic at poor visibility.';
        $packet['layers']['research_interpretation'] = 'Conflict is terminology, not a simple Art. 36 vs Art. 42 override.';
        $packet['layers']['proposed_editorial_correction'] = 'Clarified EN using 優先道路; imported answer untouched; conflicting_evidence retained.';

        return $packet;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildFigureInventoryRow(QuestionImportEntry $entry): array
    {
        $asset = $this->signs->resolve($entry);

        return [
            'original_question_number' => (int) $entry->original_question_number,
            'source_entry_id' => $entry->id,
            'source_stable_id' => $entry->stableSourceId(),
            'sign_key' => $entry->sign_key,
            'figure_caption' => $entry->figure_caption,
            'media_availability' => $entry->media_availability?->value,
            'figure_review_status_db' => $entry->figure_review_status->value,
            'asset_check' => [
                'html_source_path' => $asset['html_source_path'],
                'html_source_exists' => $asset['html_source_exists'],
                'present_in_signs_object' => $asset['present_in_signs_object'],
                'expression_preview' => isset($asset['expression'])
                    ? mb_substr((string) $asset['expression'], 0, 120)
                    : null,
                'svg_extractable' => filled($asset['svg_markup']),
                'classification' => $asset['classification'],
                'notes' => $asset['notes'],
            ],
            'editorial_checks_pending' => [
                'matches_referenced_question' => 'pending_human_review',
                'contains_essential_information' => 'pending_human_review',
                'changes_statement_meaning_if_absent' => 'likely_yes_for_sign_or_diagram_items',
                'japanese_left_side_geometry' => 'pending_human_review',
                'figure_reuse_rights_documented' => false,
            ],
            'replacement_proposal' => [
                'required' => $asset['classification'] === 'missing' || $asset['classification'] === 'mismatched',
                'status' => 'none_generated',
                'candidate_path' => null,
                'note' => $asset['present_in_signs_object']
                    ? 'Inline SVG exists in private import HTML; no speculative MenkyoNavi replacement generated. Editorial verification still required.'
                    : 'Missing SIGNS asset — replacement may be proposed later with left-side Japanese geometry after context review.',
            ],
            'does_file_existence_equal_human_verification' => false,
        ];
    }

    /**
     * @param  Collection<int, QuestionImportEntry>  $entries
     * @return array<string, mixed>
     */
    private function buildBatchRightsSection(?QuestionImportBatch $batch, Collection $entries): array
    {
        $status = $batch?->content_rights_status instanceof BatchContentRightsStatus
            ? $batch->content_rights_status
            : BatchContentRightsStatus::tryFrom((string) ($batch?->content_rights_status ?? BatchContentRightsStatus::NoEvidenceSubmitted->value))
                ?? BatchContentRightsStatus::NoEvidenceSubmitted;

        return [
            'question_import_batch_id' => $batch?->id,
            'source_key' => $batch?->source_key,
            'source_filename' => $batch?->source_filename,
            'source_relative_path' => $batch?->source_relative_path,
            'file_checksum' => $batch?->file_checksum,
            'content_rights_status' => $status->value,
            'content_rights_status_label' => $status->label(),
            'claimed_creator' => $batch?->claimed_creator,
            'origin_url' => $batch?->origin_url,
            'acquisition_notes' => $batch?->acquisition_notes,
            'license_notes' => $batch?->license_notes,
            'permission_notes' => $batch?->permission_notes,
            'reuse_scope_notes' => $batch?->reuse_scope_notes,
            'figure_rights_notes' => $batch?->figure_rights_notes,
            'rights_evidence' => $batch?->rights_evidence,
            'rights_document_path' => $batch?->rights_document_path,
            'rights_document_original_name' => $batch?->rights_document_original_name,
            'content_rights_reviewed_by' => $batch?->content_rights_reviewed_by,
            'content_rights_reviewed_at' => $batch?->content_rights_reviewed_at?->toIso8601String(),
            'research_suggestion' => [
                'status_remains' => 'not_cleared / '.$status->value,
                'known_evidence' => [
                    'Private HTML file stored at storage/app/'.$batch?->source_relative_path,
                    'Batch source_key='.$batch?->source_key,
                    'Public accessibility of similar quiz pages elsewhere is NOT a license.',
                ],
                'missing_evidence' => [
                    'Written permission from rights holder',
                    'License text covering site publication + advertising',
                    'Explicit figure/SVG reuse permission',
                    'Translation/derivative-work permission',
                    'Authorized human clearance decision',
                ],
                'necessary_human_decisions' => [
                    'Identify publisher/author of the imported HTML bank.',
                    'Decide whether MenkyoNavi may reproduce, translate, and monetize with ads.',
                    'Keep status uncleared until documents are assessed — do not auto-clear.',
                ],
            ],
            'entry_level_rights_still_unclear_count' => $entries->where('content_rights_status.value', 'unclear')->count(),
            'automated_research_did_not_clear_rights' => true,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $priorityPackets
     * @return list<array<string, mixed>>
     */
    private function collectProposedCorrections(array $priorityPackets): array
    {
        $rows = [];
        foreach ($priorityPackets as $packet) {
            if (($packet['proposed_correction'] ?? null) === null) {
                continue;
            }
            $rows[] = [
                'original_question_number' => $packet['original_question_number'],
                'source_entry_id' => $packet['source_entry_id'],
                'proposal' => $packet['proposed_correction'],
                'applied_to_imported_source' => false,
            ];
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function source(
        string $title,
        string $organization,
        string $url,
        string $section,
        string $claimSupported,
        string $relationship,
        string $uncertainty,
    ): array {
        return [
            'title' => $title,
            'organization' => $organization,
            'url' => $url,
            'section' => $section,
            'checked_at' => '2026-10-10',
            'claim_supported' => $claimSupported,
            'relationship' => $relationship,
            'uncertainty' => $uncertainty,
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function toMarkdown(array $report): string
    {
        $lines = [];
        $lines[] = '# Karimen Test 1 — Priority Review (Task 33, private)';
        $lines[] = '';
        $lines[] = 'Generated: '.$report['generated_at'];
        $lines[] = '';
        $lines[] = '> Research packets are **not** human approval, answer verification, rights clearance, or publication.';
        $lines[] = '';
        $lines[] = '## Section A — Priority questions';
        $lines[] = '';
        foreach ($report['section_a_priority_questions'] as $packet) {
            $lines[] = '### Q'.$packet['original_question_number'].' (id '.$packet['source_entry_id'].')';
            $lines[] = '';
            $lines[] = '- **Imported statement:** '.$packet['imported']['english_question'];
            $lines[] = '- **Imported answer:** '.($packet['imported']['correct_answer'] ? 'True' : 'False');
            $lines[] = '- **Explanation:** '.$packet['imported']['english_explanation'];
            $lines[] = '- **Flags / accuracy:** `'.implode(',', $packet['historical_flags']['review_flags']).'` / `'.$packet['historical_flags']['accuracy_evidence_status'].'`';
            $lines[] = '- **Legal topic:** '.$packet['legal_topic'];
            $lines[] = '- **Unresolved ambiguity:** '.$packet['unresolved_ambiguity'];
            $lines[] = '- **Proposed action:** '.$packet['proposed_editorial_action'];
            $lines[] = '- **Confidence:** '.$packet['confidence_and_limitations'];
            $lines[] = '- **Human still must decide:**';
            foreach ($packet['human_reviewer_still_must_decide'] as $item) {
                $lines[] = '  - '.$item;
            }
            if (! empty($packet['article_comparison'])) {
                $lines[] = '- **Art. comparison:**';
                foreach ($packet['article_comparison'] as $row) {
                    $lines[] = '  - '.$row['provision'].': '.$row['situation'].' | exceptions: '.$row['exceptions'];
                }
            }
            $lines[] = '';
        }

        $lines[] = '## Section B — Figure inventory (14)';
        $lines[] = '';
        $lines[] = '| Q# | ID | sign_key | media | DB figure status | Asset classification | SVG extractable |';
        $lines[] = '| ---: | ---: | --- | --- | --- | --- | --- |';
        foreach ($report['section_b_figure_inventory'] as $row) {
            $lines[] = '| '.$row['original_question_number']
                .' | '.$row['source_entry_id']
                .' | '.($row['sign_key'] ?? '—')
                .' | '.($row['media_availability'] ?? '—')
                .' | '.$row['figure_review_status_db']
                .' | '.$row['asset_check']['classification']
                .' | '.($row['asset_check']['svg_extractable'] ? 'yes' : 'no')
                .' |';
        }
        $lines[] = '';
        $lines[] = 'File presence ≠ human verification. Figure-reuse rights remain undocumented.';
        $lines[] = '';

        $rights = $report['section_c_source_rights_review'];
        $lines[] = '## Section C — Source-rights review';
        $lines[] = '';
        $lines[] = '- **Batch:** '.$rights['source_key'].' / '.$rights['source_filename'];
        $lines[] = '- **Status:** '.$rights['content_rights_status'].' ('.$rights['content_rights_status_label'].')';
        $lines[] = '- **Missing:** '.implode('; ', $rights['research_suggestion']['missing_evidence']);
        $lines[] = '';

        $lines[] = '## Section D — Proposed corrections (not applied)';
        $lines[] = '';
        foreach ($report['section_d_proposed_corrections'] as $row) {
            $proposal = $row['proposal'];
            $lines[] = '### Q'.$row['original_question_number'];
            $lines[] = '- Applied to imported source: **no**';
            $lines[] = '- Imported answer unchanged: '.(($proposal['keep_imported_answer_unchanged'] ?? false) ? 'yes' : 'no');
            if (! empty($proposal['proposed_clarified_statement_en'])) {
                $lines[] = '- Proposed clarified EN: '.$proposal['proposed_clarified_statement_en'];
            }
            if (! empty($proposal['recommend_withhold'])) {
                $lines[] = '- Recommend withhold: yes';
            }
            $lines[] = '';
        }

        $blockers = $report['section_e_remaining_blockers'];
        $lines[] = '## Section E — Remaining blockers';
        $lines[] = '';
        $lines[] = '- Publication-ready count: **'.$blockers['publication_ready_count'].'**';
        $lines[] = '- Blocked: '.$blockers['blocked_count'];
        $lines[] = '';
        foreach ($blockers['human_decisions_required'] as $item) {
            $lines[] = '- '.$item;
        }
        $lines[] = '';

        return implode("\n", $lines);
    }

    private function assertPrivateExportPath(string $path): void
    {
        $normalized = str_replace('\\', '/', $path);
        $root = str_replace('\\', '/', storage_path('app/menkyo-exports'));

        if (! str_starts_with($normalized, $root.'/') && $normalized !== $root) {
            throw new RuntimeException('Priority review exports must remain under storage/app/menkyo-exports.');
        }

        if (str_contains($normalized, '/public/')) {
            throw new RuntimeException('Priority review exports must not be written under the public directory.');
        }
    }
}
