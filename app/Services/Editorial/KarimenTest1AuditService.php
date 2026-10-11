<?php

namespace App\Services\Editorial;

use App\Enums\AccuracyEvidenceStatus;
use App\Enums\AnswerKeyReviewStatus;
use App\Enums\ContentRightsReviewStatus;
use App\Enums\FigureReviewStatus;
use App\Enums\StagedTranslationStatus;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportEntryTranslation;
use App\Services\Translations\TranslationQualityChecker;
use App\Support\SupportedLocale;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Private editorial/legal-readiness audit for karimen_test_1.
 *
 * Automated findings are suggestions only — not native-speaker review,
 * regulatory verification, or publication approval.
 */
class KarimenTest1AuditService
{
    public const SOURCE_GROUP = 'karimen_test_1';

    /** @var list<int> */
    public const PRIORITY_JA_NEEDS_REVIEW_NUMBERS = [2, 6, 8, 10, 13, 19, 22, 28, 31, 33, 35, 38, 39, 42, 45, 47];

    /** @var list<int> */
    public const UNTRANSLATED_REVIEW_QUEUE_NUMBERS = [4, 23];

    public function __construct(
        private TranslationQualityChecker $qualityChecker,
        private KarimenTest1EvidenceLedger $evidenceLedger,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function audit(): array
    {
        /** @var Collection<int, QuestionImportEntry> $entries */
        $entries = QuestionImportEntry::query()
            ->where('source_group', self::SOURCE_GROUP)
            ->with('translations')
            ->orderBy('original_question_number')
            ->get();

        if ($entries->count() !== 50) {
            throw new RuntimeException(
                'Expected 50 karimen_test_1 entries, found '.$entries->count().'.'
            );
        }

        $items = [];
        $locales = SupportedLocale::codes();

        foreach ($entries as $entry) {
            $items[] = $this->auditEntry($entry, $locales);
        }

        $summary = $this->summarize($items, $locales);
        /** @var QuestionImportEntry $firstEntry */
        $firstEntry = $entries->first();

        return [
            'format_version' => 2,
            'generated_at' => now()->toIso8601String(),
            'private' => true,
            'source_group' => self::SOURCE_GROUP,
            'disclaimer' => [
                'automated_findings_are_not_human_review' => true,
                'automated_findings_are_not_regulatory_verification' => true,
                'no_invented_citations' => true,
                'english_source_immutable' => true,
                'answers_immutable' => true,
                'task_16_gap_note' => 'Q28–Q50 may still lack full eight-locale drafts; this audit does not create translations.',
                'task_32_evidence_collected_is_not_verified' => true,
                'task_32_no_mass_approval' => true,
                'task_32_note' => 'accuracy_evidence_status evidence_collected means sources were recorded, not that the answer key is verified or publication-ready. Do not mass-approve questions from this audit.',
            ],
            'evidence_workflow' => $this->evidenceWorkflowTemplate(),
            'task_16_state' => $summary['task_16_state'],
            'task_32_state' => $summary['task_32_state'],
            'batch_rights' => $this->evidenceLedger->batchRightsSnapshot($firstEntry->batch),
            'readiness' => $summary['readiness'],
            'priority_review_queue' => $summary['priority_review_queue'],
            'items' => $items,
        ];
    }

    /**
     * @param  array<string, mixed>  $audit
     * @return array{json_path: string, markdown_path: string}
     */
    public function writePrivateReports(array $audit, ?string $jsonPath = null, ?string $markdownPath = null): array
    {
        $jsonPath ??= storage_path('app/menkyo-exports/karimen_test_1_full_audit.json');
        $markdownPath ??= storage_path('app/menkyo-exports/karimen_test_1_review_summary.md');

        $this->assertPrivateExportPath($jsonPath);
        $this->assertPrivateExportPath($markdownPath);

        File::ensureDirectoryExists(dirname($jsonPath));
        File::put(
            $jsonPath,
            json_encode($audit, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
        );
        File::put($markdownPath, $this->toMarkdown($audit));

        return [
            'json_path' => $jsonPath,
            'markdown_path' => $markdownPath,
        ];
    }

    /**
     * @param  list<string>  $locales
     * @return array<string, mixed>
     */
    private function auditEntry(QuestionImportEntry $entry, array $locales): array
    {
        $number = (int) $entry->original_question_number;
        $availability = [];
        $localeQuality = [];
        $completeLocales = 0;

        foreach ($locales as $locale) {
            $translation = $entry->translationFor($locale);
            $complete = $translation !== null && $translation->hasCompleteText();
            if ($complete) {
                $completeLocales++;
            }

            $flags = [];
            if ($translation !== null) {
                $flags = $this->qualityChecker->check($entry, [
                    'locale' => $locale,
                    'question_text' => $translation->question_text,
                    'explanation' => $translation->explanation,
                    'figure_caption' => $translation->figure_caption,
                ]);
            } elseif ($locale !== 'en') {
                $flags = ['missing_translation_record'];
            }

            $availability[$locale] = [
                'present' => $translation !== null,
                'complete' => $complete,
                'status' => $translation?->status?->value,
                'quality_flags' => $flags,
                'has_figure_caption' => filled($translation?->figure_caption),
            ];

            if ($flags !== []) {
                $localeQuality[$locale] = $flags;
            }
        }

        $japanese = $entry->translationFor('ja');
        $finding = $this->classifyJapaneseFinding($entry, $japanese, $availability['ja']['quality_flags'] ?? []);
        $figureDependent = filled($entry->figure_caption) || filled($entry->sign_key);
        $blockers = $entry->computePublicationBlockers();
        $special = $this->specialQueueNotes($entry, $japanese);
        $ledgerCatalog = $this->evidenceLedger->catalogFor($entry);
        $evidenceLedger = array_merge($ledgerCatalog, [
            'accuracy_evidence_status' => $entry->accuracy_evidence_status->value,
            'legal_topic' => $entry->legal_topic,
            'accuracy_evidence_sources' => $entry->accuracy_evidence_sources,
            'figure_review_status' => $entry->figure_review_status->value,
            'figure_review_notes' => $entry->figure_review_notes,
            'proposed_correct_answer' => $entry->proposed_correct_answer,
            'catalog_suggested_accuracy_evidence_status' => $ledgerCatalog['accuracy_evidence_status'],
            'catalog_suggested_figure_review_status' => $ledgerCatalog['figure_review_status'],
        ]);

        return [
            'source_entry_id' => $entry->id,
            'source_stable_id' => $entry->stableSourceId(),
            'original_question_number' => $number,
            'source_group' => $entry->source_group,
            'exam_family' => $entry->exam_family,
            'database_facts' => [
                'english_question' => $entry->english_question,
                'english_explanation' => $entry->english_explanation,
                'correct_answer' => (bool) $entry->correct_answer,
                'figure_caption' => $entry->figure_caption,
                'sign_key' => $entry->sign_key,
                'source_entry_checksum' => $entry->source_entry_checksum,
                'review_flags' => $entry->review_flags ?? [],
                'editorial_issues' => $entry->editorial_issues ?? [],
                'answer_key_status' => $entry->answer_key_status?->value,
                'answer_key_notes' => $entry->answer_key_notes,
                'answer_key_reference' => $entry->answer_key_reference,
                'answer_key_verified_at' => $entry->answer_key_verified_at?->toIso8601String(),
                'content_rights_status' => $entry->content_rights_status?->value,
                'content_rights_notes' => $entry->content_rights_notes,
                'content_rights_evidence' => $entry->content_rights_evidence,
                'accuracy_evidence_status' => $entry->accuracy_evidence_status->value,
                'legal_topic' => $entry->legal_topic,
                'accuracy_evidence_sources' => $entry->accuracy_evidence_sources,
                'accuracy_verification_notes' => $entry->accuracy_verification_notes,
                'figure_review_status' => $entry->figure_review_status->value,
                'figure_review_notes' => $entry->figure_review_notes,
                'proposed_correct_answer' => $entry->proposed_correct_answer,
                'is_published' => (bool) $entry->is_published,
                'promoted_question_id' => $entry->promoted_question_id,
                'japanese_question' => $japanese?->question_text,
                'japanese_explanation' => $japanese?->explanation,
                'japanese_figure_caption' => $japanese?->figure_caption,
                'japanese_status' => $japanese?->status?->value,
                'japanese_nuance_note' => $japanese?->nuance_note,
                'japanese_quality_flags' => $japanese?->quality_flags ?? [],
            ],
            'translation_availability_by_locale' => $availability,
            'eight_language_draft_complete' => $completeLocales === count($locales),
            'complete_locale_count' => $completeLocales,
            'figure_dependent' => $figureDependent,
            'evidence_ledger' => $evidenceLedger,
            'publication_blockers' => $blockers,
            'publication_ready' => $blockers === [],
            'missing_evidence' => [
                'answer_key_verified_evidence' => $entry->answer_key_status !== AnswerKeyReviewStatus::Verified
                    || blank($entry->answer_key_reference),
                'content_rights_authorization_evidence' => $entry->content_rights_status !== ContentRightsReviewStatus::ApprovedForPublicReuse
                    || blank($entry->content_rights_evidence),
            ],
            'automated_review_suggestions' => [
                'japanese_finding' => $finding,
                'multilingual_quality_flags' => $localeQuality,
                'suggested_next_editorial_action' => $this->suggestedAction($entry, $finding, $figureDependent, $completeLocales, count($locales)),
                'special_queue' => $special,
                'note' => 'These suggestions are AI/automated audit outputs, not verified legal conclusions.',
            ],
        ];
    }

    /**
     * @param  list<string>  $qualityFlags
     */
    private function classifyJapaneseFinding(
        QuestionImportEntry $entry,
        ?QuestionImportEntryTranslation $japanese,
        array $qualityFlags,
    ): string {
        if (($entry->review_flags ?? []) !== []) {
            return 'original_wording_unclear';
        }

        if ($japanese === null || ! $japanese->hasCompleteText()) {
            return 'original_wording_unclear';
        }

        if (filled($entry->figure_caption) || filled($entry->sign_key)) {
            return 'figure_interpretation_required';
        }

        if ($qualityFlags !== []) {
            return 'translation_issue_suspected';
        }

        if ($japanese->status === StagedTranslationStatus::NeedsReview) {
            return 'regulatory_evidence_required';
        }

        // Unreviewed drafts still require human/regulatory checks even when automation is clean.
        return 'no_obvious_automated_inconsistency';
    }

    /**
     * @return array<string, mixed>|null
     */
    private function specialQueueNotes(
        QuestionImportEntry $entry,
        ?QuestionImportEntryTranslation $japanese,
    ): ?array {
        $number = (int) $entry->original_question_number;

        if (! in_array($number, [...self::PRIORITY_JA_NEEDS_REVIEW_NUMBERS, ...self::UNTRANSLATED_REVIEW_QUEUE_NUMBERS], true)) {
            return null;
        }

        $reason = match ($number) {
            2, 6, 8, 10, 13, 22, 28, 31, 33, 35, 39, 42, 45, 47 => 'Figure/sign-dependent draft previously marked needs_review; visual meaning must be confirmed against the illustration.',
            19 => 'Learner-permit sign terminology (仮免許練習中 vs 初心運転者) requires editorial confirmation against official wording.',
            38 => 'Priority-road / 徐行 obligation nuance may need regulatory confirmation; keep answer immutable until verified.',
            4 => 'Import flag ambiguous_terminology: soft “give-and-take spirit” attitude wording is not a clear regulatory True/False rule.',
            23 => 'Import flag soft_or_awkward_phrasing: “proceed at the maximum speed limit” conflicts with safe-speed teaching in the explanation.',
            default => 'Priority editorial review item.',
        };

        $possibleIssue = match ($number) {
            4, 23 => 'No Japanese draft should be invented until source wording/intent is clarified.',
            19 => 'Possible terminology mismatch for learner-permit identification plate.',
            38 => 'Possible overstatement of 徐行 duty on priority roads at uncontrolled intersections.',
            default => filled($entry->sign_key) || filled($entry->figure_caption)
                ? 'Sign/figure interpretation may reverse True/False meaning if the wrong plate is assumed.'
                : 'Draft retained for human linguistic/regulatory review.',
        };

        $evidenceNeeded = match ($number) {
            4, 23 => 'Editorial clarification of intended examination claim; then decide whether a JA draft is appropriate.',
            default => 'Authoritative Japanese traffic rule reference (NPA / statute / official license guidance) plus figure confirmation when applicable.',
        };

        return [
            'priority' => true,
            'exact_english_question' => $entry->english_question,
            'exact_english_explanation' => $entry->english_explanation,
            'japanese_draft' => $japanese?->question_text,
            'japanese_explanation' => $japanese?->explanation,
            'reason_for_review' => $reason,
            'possible_translation_issue' => $possibleIssue,
            'figure_or_sign_dependency' => [
                'figure_caption' => $entry->figure_caption,
                'sign_key' => $entry->sign_key,
                'required' => filled($entry->figure_caption) || filled($entry->sign_key),
            ],
            'evidence_or_clarification_needed' => $evidenceNeeded,
            'suggested_editorial_action' => in_array($number, self::UNTRANSLATED_REVIEW_QUEUE_NUMBERS, true)
                ? 'Keep in clarification queue; do not auto-translate.'
                : 'Human review of JA draft against English + figure; record evidence in answer-key/rights panels; keep status below reviewed until verified.',
        ];
    }

    private function suggestedAction(
        QuestionImportEntry $entry,
        string $finding,
        bool $figureDependent,
        int $completeLocales,
        int $localeCount,
    ): string {
        if (in_array((int) $entry->original_question_number, self::UNTRANSLATED_REVIEW_QUEUE_NUMBERS, true)) {
            return 'Clarify source wording before any Japanese draft; keep unpublished.';
        }

        if ($entry->content_rights_status->blocksPublication()) {
            $rights = 'Obtain and document content-reuse authorization.';
        } else {
            $rights = 'Rights already marked approved — re-check supporting evidence remains on file.';
        }

        if ($entry->answer_key_status->blocksPublication()) {
            $answer = 'Locate authoritative Japanese evidence and record it in answer-key review without changing the original Boolean.';
        } else {
            $answer = 'Answer key already verified — preserve evidence references.';
        }

        $translation = match ($finding) {
            'figure_interpretation_required' => 'Confirm figure/sign meaning before advancing translation status.',
            'translation_issue_suspected' => 'Inspect automated quality flags and correct unreviewed drafts only with documented justification.',
            'original_wording_unclear' => 'Resolve source ambiguity / missing JA draft first.',
            default => 'Schedule native-speaker editorial review; automation found no hard inconsistency.',
        };

        $coverage = $completeLocales < $localeCount
            ? 'Complete missing locale drafts after JA stabilizes (Task 16 gap for Q28–Q50).'
            : 'Eight-locale draft coverage present; still requires human review.';

        return implode(' ', [$translation, $answer, $rights, $coverage]);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  list<string>  $locales
     * @return array{task_16_state: array<string, mixed>, task_32_state: array<string, mixed>, readiness: array<string, mixed>, priority_review_queue: list<array<string, mixed>>}
     */
    private function summarize(array $items, array $locales): array
    {
        $jaDrafts = 0;
        $eightComplete = 0;
        $jaNeedsReview = 0;
        $reviewedTranslations = 0;
        $unresolvedTranslationIssues = 0;
        $verifiedAnswers = 0;
        $rightsCleared = 0;
        $figureDependent = 0;
        $publicationReady = 0;
        $blocked = 0;
        $missingByLocale = array_fill_keys($locales, 0);
        $findingCounts = [];
        $priorityQueue = [];
        $withTranslationsEightLocale = 0;
        $needingTranslation = 0;
        $figureMissing = 0;
        $wordingAmbiguity = 0;
        $accuracyEvidenceCollected = 0;
        $accuracyConflicting = 0;
        $accuracyAwaitingVerification = 0;
        $rightsDocumented = 0;
        $rightsUnknown = 0;
        $accuracyStatusCounts = array_fill_keys(array_map(
            static fn (AccuracyEvidenceStatus $status): string => $status->value,
            AccuracyEvidenceStatus::cases(),
        ), 0);
        $figureStatusCounts = array_fill_keys(array_map(
            static fn (FigureReviewStatus $status): string => $status->value,
            FigureReviewStatus::cases(),
        ), 0);

        foreach ($items as $item) {
            $facts = $item['database_facts'];
            $ledger = $item['evidence_ledger'];
            if (filled($facts['japanese_question'])) {
                $jaDrafts++;
            }
            if ($item['eight_language_draft_complete']) {
                $eightComplete++;
                $withTranslationsEightLocale++;
            } else {
                $needingTranslation++;
            }
            if (($facts['japanese_status'] ?? null) === StagedTranslationStatus::NeedsReview->value) {
                $jaNeedsReview++;
            }
            if (in_array($facts['japanese_status'] ?? null, [
                StagedTranslationStatus::Reviewed->value,
                StagedTranslationStatus::RegulatoryVerificationPending->value,
                StagedTranslationStatus::ReadyForPublication->value,
            ], true)) {
                $reviewedTranslations++;
            }
            if (($item['automated_review_suggestions']['japanese_finding'] ?? null) !== 'no_obvious_automated_inconsistency'
                || ($item['translation_availability_by_locale']['ja']['quality_flags'] ?? []) !== []) {
                $unresolvedTranslationIssues++;
            }
            if ($facts['answer_key_status'] === AnswerKeyReviewStatus::Verified->value) {
                $verifiedAnswers++;
            }
            if ($facts['content_rights_status'] === ContentRightsReviewStatus::ApprovedForPublicReuse->value) {
                $rightsCleared++;
            }
            if ($item['figure_dependent']) {
                $figureDependent++;
            }

            $figureStatus = FigureReviewStatus::from($facts['figure_review_status']);
            $figureStatusCounts[$figureStatus->value] = ($figureStatusCounts[$figureStatus->value] ?? 0) + 1;

            if ($figureStatus === FigureReviewStatus::FigureMissing
                || ($item['figure_dependent'] && $figureStatus->blocksPublicationReadiness())) {
                $figureMissing++;
            }

            $accuracyStatus = AccuracyEvidenceStatus::from($facts['accuracy_evidence_status']);
            $accuracyStatusCounts[$accuracyStatus->value] = ($accuracyStatusCounts[$accuracyStatus->value] ?? 0) + 1;

            if ($accuracyStatus === AccuracyEvidenceStatus::EvidenceCollected) {
                $accuracyEvidenceCollected++;
            }
            if ($accuracyStatus === AccuracyEvidenceStatus::ConflictingEvidence) {
                $accuracyConflicting++;
            }
            if ($accuracyStatus !== AccuracyEvidenceStatus::HumanReviewed) {
                $accuracyAwaitingVerification++;
            }

            $ledgerWordingFlag = (bool) ($ledger['wording_ambiguity']['flag'] ?? false);
            if (($facts['review_flags'] ?? []) !== []
                || $accuracyStatus === AccuracyEvidenceStatus::NeedsClarification
                || $ledgerWordingFlag) {
                $wordingAmbiguity++;
            }

            if ($facts['content_rights_status'] === ContentRightsReviewStatus::ApprovedForPublicReuse->value) {
                $rightsDocumented++;
            }
            if ($facts['content_rights_status'] === ContentRightsReviewStatus::Unclear->value) {
                $rightsUnknown++;
            }

            if ($item['publication_ready']) {
                $publicationReady++;
            } else {
                $blocked++;
            }

            foreach ($locales as $locale) {
                if (! ($item['translation_availability_by_locale'][$locale]['complete'] ?? false)) {
                    $missingByLocale[$locale]++;
                }
            }

            $finding = $item['automated_review_suggestions']['japanese_finding'];
            $findingCounts[$finding] = ($findingCounts[$finding] ?? 0) + 1;

            if (($item['automated_review_suggestions']['special_queue'] ?? null) !== null) {
                $priorityQueue[] = [
                    'original_question_number' => $item['original_question_number'],
                    'source_entry_id' => $item['source_entry_id'],
                    'special_queue' => $item['automated_review_suggestions']['special_queue'],
                ];
            }
        }

        $q2850IncompleteNonJa = collect($items)
            ->filter(fn (array $item): bool => $item['original_question_number'] >= 28
                && $item['original_question_number'] <= 50
                && ! $item['eight_language_draft_complete'])
            ->count();

        return [
            'task_16_state' => [
                'total_questions' => count($items),
                'japanese_drafts' => $jaDrafts,
                'eight_language_complete' => $eightComplete,
                'missing_by_locale' => $missingByLocale,
                'ja_needs_review' => $jaNeedsReview,
                'q28_50_seven_language_gap' => $q2850IncompleteNonJa,
                'task_16_complete' => $q2850IncompleteNonJa === 0 && $eightComplete === 48,
                'untranslated_numbers' => self::UNTRANSLATED_REVIEW_QUEUE_NUMBERS,
            ],
            'task_32_state' => [
                'total_questions' => count($items),
                'with_translations_eight_locale' => $withTranslationsEightLocale,
                'needing_translation' => $needingTranslation,
                'figure_dependent' => $figureDependent,
                'figure_missing' => $figureMissing,
                'wording_ambiguity' => $wordingAmbiguity,
                'accuracy_evidence_collected' => $accuracyEvidenceCollected,
                'accuracy_conflicting' => $accuracyConflicting,
                'accuracy_awaiting_verification' => $accuracyAwaitingVerification,
                'rights_documented' => $rightsDocumented,
                'rights_unknown' => $rightsUnknown,
                'meeting_all_recorded_criteria' => $publicationReady,
                'blocked_from_publication' => $blocked,
            ],
            'readiness' => [
                'total_questions' => count($items),
                'japanese_draft_coverage' => $jaDrafts,
                'eight_language_draft_coverage' => $eightComplete,
                'reviewed_translations' => $reviewedTranslations,
                'unresolved_translation_issues' => $unresolvedTranslationIssues,
                'verified_answer_keys' => $verifiedAnswers,
                'rights_cleared_questions' => $rightsCleared,
                'questions_with_unresolved_figures' => $figureDependent,
                'questions_blocked_from_publication' => $blocked,
                'fully_publication_ready_questions' => $publicationReady,
                'japanese_finding_counts' => $findingCounts,
                'accuracy_evidence_status_counts' => $accuracyStatusCounts,
                'figure_review_status_counts' => $figureStatusCounts,
                'note' => 'Translation completeness is not publication readiness. evidence_collected is not human verification. Mock exams must not be enabled from drafts alone.',
            ],
            'priority_review_queue' => $priorityQueue,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function evidenceWorkflowTemplate(): array
    {
        return [
            'admin_panels' => [
                'answer_key_review' => [
                    'status_field' => 'answer_key_status',
                    'notes_field' => 'answer_key_notes',
                    'reference_field' => 'answer_key_reference',
                    'proposed_correction_fields' => ['proposed_correct_answer', 'proposed_answer_notes'],
                    'recommended_notes_structure' => [
                        'source_title',
                        'issuing_organization',
                        'url_or_publication_identifier',
                        'relevant_section_or_page',
                        'publication_or_edition_date',
                        'verification_date',
                        'reviewer',
                        'evidence_notes',
                    ],
                ],
                'content_rights_review' => [
                    'status_field' => 'content_rights_status',
                    'notes_field' => 'content_rights_notes',
                    'evidence_field' => 'content_rights_evidence',
                ],
            ],
            'acceptable_source_categories' => [
                'National Police Agency publications',
                'Official Japanese traffic legislation',
                'Prefectural police driving-license guidance',
                'Government road-safety resources',
                'JAF educational information when suitable and authorized',
            ],
            'insufficient_as_rights_or_legal_proof' => [
                'Possession of an HTML file',
                'Source-group name containing JAF',
                'Accessible public website alone',
                'Automatically generated question text',
                'Missing copyright notice',
            ],
            'rule' => 'Do not invent citations. Keep verification_pending / unclear until real evidence is recorded.',
        ];
    }

    /**
     * @param  array<string, mixed>  $audit
     */
    private function toMarkdown(array $audit): string
    {
        $task16 = $audit['task_16_state'];
        $task32 = $audit['task_32_state'] ?? [];
        $batchRights = $audit['batch_rights'] ?? [];
        $ready = $audit['readiness'];
        $lines = [];
        $lines[] = '# Karimen Test 1 — Editorial Review Summary (private)';
        $lines[] = '';
        $lines[] = 'Generated: '.$audit['generated_at'];
        $lines[] = '';
        $lines[] = '> Automated/AI findings are **not** human translation review, regulatory verification, rights clearance, or publication approval.';
        $lines[] = '';
        $lines[] = '## Task 16 verification';
        $lines[] = '';
        $lines[] = '| Metric | Value |';
        $lines[] = '| --- | ---: |';
        $lines[] = '| Total questions | '.$task16['total_questions'].' |';
        $lines[] = '| Japanese drafts | '.$task16['japanese_drafts'].' |';
        $lines[] = '| Eight-language complete | '.$task16['eight_language_complete'].' |';
        $lines[] = '| JA needs_review | '.$task16['ja_needs_review'].' |';
        $lines[] = '| Q28–Q50 seven-language gap | '.$task16['q28_50_seven_language_gap'].' |';
        $lines[] = '| Task 16 complete | '.($task16['task_16_complete'] ? 'yes' : '**no**').' |';
        $lines[] = '| Untranslated (held) | '.implode(', ', array_map(fn ($n) => 'Q'.$n, $task16['untranslated_numbers'])).' |';
        $lines[] = '';
        $lines[] = 'Missing by locale: `'.json_encode($task16['missing_by_locale'], JSON_UNESCAPED_UNICODE).'`';
        $lines[] = '';
        $lines[] = '## Task 32 — evidence & rights (private)';
        $lines[] = '';
        $lines[] = '> evidence_collected ≠ verified. This audit does not mass-approve publication.';
        $lines[] = '';
        if ($task32 !== []) {
            $lines[] = '| Metric | Count |';
            $lines[] = '| --- | ---: |';
            foreach ([
                'with_translations_eight_locale' => 'Eight-locale translation complete',
                'needing_translation' => 'Needing translation (not 8-complete)',
                'figure_dependent' => 'Figure/sign dependent',
                'figure_missing' => 'Figure missing / figure blocker',
                'wording_ambiguity' => 'Wording ambiguity flagged',
                'accuracy_evidence_collected' => 'Accuracy evidence collected',
                'accuracy_conflicting' => 'Accuracy conflicting evidence',
                'accuracy_awaiting_verification' => 'Accuracy awaiting human review',
                'rights_documented' => 'Rights documented (approved reuse)',
                'rights_unknown' => 'Rights unknown (unclear)',
                'meeting_all_recorded_criteria' => 'Meeting all recorded criteria',
                'blocked_from_publication' => 'Blocked from publication',
            ] as $key => $label) {
                if (array_key_exists($key, $task32)) {
                    $lines[] = '| '.$label.' | '.$task32[$key].' |';
                }
            }
            $lines[] = '';
        }
        $lines[] = '### Copyright batch status';
        $lines[] = '';
        $lines[] = '- **Status:** '.($batchRights['status'] ?? 'unknown');
        $lines[] = '- **Batch file:** '.($batchRights['source_filename'] ?? '—');
        $lines[] = '- **Note:** '.($batchRights['note'] ?? '—');
        $lines[] = '';
        $lines[] = '### Accuracy evidence status counts';
        $lines[] = '';
        $lines[] = '`'.json_encode($ready['accuracy_evidence_status_counts'] ?? [], JSON_UNESCAPED_UNICODE).'`';
        $lines[] = '';
        $lines[] = '### Figure review status counts';
        $lines[] = '';
        $lines[] = '`'.json_encode($ready['figure_review_status_counts'] ?? [], JSON_UNESCAPED_UNICODE).'`';
        $lines[] = '';
        $lines[] = '### Ambiguous / conflicting wording (Q4, Q23, Q38)';
        $lines[] = '';
        foreach ([4, 23, 38] as $ambiguousNumber) {
            $item = collect($audit['items'])->firstWhere('original_question_number', $ambiguousNumber);
            if ($item === null) {
                continue;
            }
            $ledger = $item['evidence_ledger'];
            $lines[] = '- **Q'.$ambiguousNumber.'** — accuracy: `'.$item['database_facts']['accuracy_evidence_status'].'`';
            if (($ledger['wording_ambiguity']['flag'] ?? false) === true) {
                $lines[] = '  - Wording: '.($ledger['wording_ambiguity']['note'] ?? 'flagged');
            }
            if (isset($ledger['special_findings'])) {
                $lines[] = '  - Special: '.$ledger['special_findings'];
            }
        }
        $lines[] = '';
        $lines[] = '### Per-question unresolved issues (compact)';
        $lines[] = '';
        foreach ($audit['items'] as $item) {
            $issues = $this->compactUnresolvedIssues($item);
            if ($issues === []) {
                continue;
            }
            $lines[] = '- **Q'.$item['original_question_number'].'** ('.$item['source_entry_id'].'): '.implode('; ', $issues);
        }
        $lines[] = '';
        $lines[] = '## Publication readiness';
        $lines[] = '';
        $lines[] = '| Metric | Count |';
        $lines[] = '| --- | ---: |';
        foreach ([
            'japanese_draft_coverage' => 'Japanese draft coverage',
            'eight_language_draft_coverage' => 'Eight-language draft coverage',
            'reviewed_translations' => 'Reviewed translations',
            'unresolved_translation_issues' => 'Unresolved translation issues (automated)',
            'verified_answer_keys' => 'Verified answer keys',
            'rights_cleared_questions' => 'Rights-cleared questions',
            'questions_with_unresolved_figures' => 'Figure/sign-dependent questions',
            'questions_blocked_from_publication' => 'Blocked from publication',
            'fully_publication_ready_questions' => 'Fully publication-ready',
        ] as $key => $label) {
            if (array_key_exists($key, $ready)) {
                $lines[] = '| '.$label.' | '.$ready[$key].' |';
            }
        }
        $lines[] = '';
        $lines[] = 'Japanese automated finding counts: `'.json_encode($ready['japanese_finding_counts'], JSON_UNESCAPED_UNICODE).'`';
        $lines[] = '';
        $lines[] = '## Priority review queue';
        $lines[] = '';
        foreach ($audit['priority_review_queue'] as $row) {
            $special = $row['special_queue'];
            $lines[] = '### Q'.$row['original_question_number'].' (id '.$row['source_entry_id'].')';
            $lines[] = '';
            $lines[] = '- **Reason:** '.$special['reason_for_review'];
            $lines[] = '- **Possible issue:** '.$special['possible_translation_issue'];
            $lines[] = '- **Evidence/clarification needed:** '.$special['evidence_or_clarification_needed'];
            $lines[] = '- **Suggested action:** '.$special['suggested_editorial_action'];
            $lines[] = '- **Figure/sign:** '.(($special['figure_or_sign_dependency']['sign_key'] ?? null) ?: '—')
                .' / '.(($special['figure_or_sign_dependency']['figure_caption'] ?? null) ?: '—');
            $lines[] = '';
        }
        $lines[] = '## Evidence workflow';
        $lines[] = '';
        $lines[] = 'Record authoritative references in the existing admin answer-key and content-rights panels.';
        $lines[] = 'Recommended note fields: source title, issuing organization, URL/publication ID, section/page, edition date, verification date, reviewer, notes.';
        $lines[] = '';
        $lines[] = 'Insufficient alone: HTML possession, “JAF” in source-group name, public website access, auto-generated text, missing copyright notice.';
        $lines[] = '';
        $lines[] = '## Per-question index';
        $lines[] = '';
        $lines[] = '| Q# | ID | Ans | JA | 8-lang | Finding | AK | Rights | Ready |';
        $lines[] = '| ---: | ---: | --- | --- | --- | --- | --- | --- | --- |';
        foreach ($audit['items'] as $item) {
            $facts = $item['database_facts'];
            $lines[] = '| '.$item['original_question_number']
                .' | '.$item['source_entry_id']
                .' | '.($facts['correct_answer'] ? 'T' : 'F')
                .' | '.(filled($facts['japanese_question']) ? ($facts['japanese_status'] ?? 'yes') : 'missing')
                .' | '.($item['eight_language_draft_complete'] ? 'yes' : 'no')
                .' | '.$item['automated_review_suggestions']['japanese_finding']
                .' | '.$facts['answer_key_status']
                .' | '.$facts['content_rights_status']
                .' | '.($item['publication_ready'] ? 'yes' : 'no')
                .' |';
        }
        $lines[] = '';
        $lines[] = 'Full machine-readable packet: `karimen_test_1_full_audit.json`';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $item
     * @return list<string>
     */
    private function compactUnresolvedIssues(array $item): array
    {
        $issues = $item['publication_blockers'] ?? [];

        $accuracy = $item['database_facts']['accuracy_evidence_status'] ?? null;
        if ($accuracy !== null && $accuracy !== AccuracyEvidenceStatus::HumanReviewed->value) {
            $issues[] = 'accuracy:'.$accuracy;
        }

        if ($item['figure_dependent'] ?? false) {
            $figure = $item['database_facts']['figure_review_status'] ?? null;
            if ($figure !== null && ! in_array($figure, [
                FigureReviewStatus::NotApplicable->value,
                FigureReviewStatus::ReviewedSufficient->value,
            ], true)) {
                $issues[] = 'figure:'.$figure;
            }
        }

        if (($item['database_facts']['review_flags'] ?? []) !== []) {
            $issues[] = 'import_flags';
        }

        if (! ($item['eight_language_draft_complete'] ?? false)) {
            $issues[] = 'incomplete_locales';
        }

        return array_values(array_unique($issues));
    }

    private function assertPrivateExportPath(string $path): void
    {
        $normalized = str_replace('\\', '/', $path);
        $root = str_replace('\\', '/', storage_path('app/menkyo-exports'));

        if (! str_starts_with($normalized, $root.'/') && $normalized !== $root) {
            throw new RuntimeException('Audit exports must remain under storage/app/menkyo-exports.');
        }

        if (str_contains($normalized, '/public/')) {
            throw new RuntimeException('Audit exports must not be written under the public directory.');
        }
    }
}
