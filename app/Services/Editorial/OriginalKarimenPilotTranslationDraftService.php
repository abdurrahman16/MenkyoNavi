<?php

namespace App\Services\Editorial;

use App\Enums\HumanAccuracyReviewDecision;
use App\Enums\StagedTranslationStatus;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportEntryTranslation;
use App\Support\SupportedLocale;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Private multilingual draft translations for menkyo_original_karimen_pilot_001.
 *
 * Research support enables drafting only. Drafts never confer native review,
 * human accuracy approval, rights clearance, or publication.
 */
class OriginalKarimenPilotTranslationDraftService
{
    public const COLLECTION_KEY = OriginalKarimenPilotStagingService::COLLECTION_KEY;

    public const SOURCE_GROUP = OriginalKarimenPilotStagingService::SOURCE_GROUP;

    public const DATA_RELATIVE_PATH = 'data/original_karimen_pilot_001_translations.php';

    public const DRAFTING_METHOD = 'ai_assisted_controlled_draft_task_46';

    /**
     * @return list<string>
     */
    public function targetLocales(): array
    {
        return ['en', 'zh', 'vi', 'fil', 'ne', 'bn', 'ko'];
    }

    /**
     * @return array{
     *     collection_key: string,
     *     drafting_method: string,
     *     target_locales: list<string>,
     *     terminology: array<string, array<string, string>>,
     *     withheld: list<array{candidate_id: string, number: int, reason: string}>,
     *     translations: array<string, array<string, array{question_text: string, explanation: string}>>
     * }
     */
    public function catalog(): array
    {
        $path = database_path(self::DATA_RELATIVE_PATH);

        if (! is_file($path)) {
            throw new RuntimeException('Original Karimen pilot translation catalog is missing: '.$path);
        }

        /** @var array<string, mixed> $catalog */
        $catalog = require $path;

        if (($catalog['collection_key'] ?? null) !== self::COLLECTION_KEY) {
            throw new RuntimeException('Pilot translation catalog collection_key mismatch.');
        }

        foreach ($this->targetLocales() as $locale) {
            if (! SupportedLocale::isSupported($locale)) {
                throw new RuntimeException("Unsupported pilot translation locale [{$locale}].");
            }
        }

        return $catalog;
    }

    /**
     * Eligible candidate numbers from research audit + live staging state.
     *
     * Candidates :1 / :8 stay withheld until a human applies the Task 47 source revision
     * (or equivalent live metadata marks source clarification resolved for the current fingerprint).
     *
     * @return list<int>
     */
    public function eligibleCandidateNumbers(): array
    {
        $audit = app(OriginalKarimenPilotAccuracyAudit::class)->catalog();
        $revisions = app(OriginalKarimenPilotSourceRevisionService::class);
        $eligible = [];

        foreach ($audit['candidates'] as $number => $row) {
            $number = (int) $number;
            $entry = $this->findStagedEntry($number);

            if ($entry === null || ! $this->isSourceStableForDrafting($entry)) {
                continue;
            }

            $assessment = (string) (data_get($entry->source_metadata, 'research_assessment') ?: ($row['research_assessment'] ?? ''));

            if ($assessment === 'supported_as_written') {
                if (in_array($number, OriginalKarimenPilotSourceRevisionService::TARGET_NUMBERS, true)
                    && ! $revisions->isSourceClarificationResolved($entry)) {
                    continue;
                }

                $eligible[] = $number;

                continue;
            }

            if ($assessment === 'supported_with_clarification'
                && in_array($number, OriginalKarimenPilotSourceRevisionService::TARGET_NUMBERS, true)
                && $revisions->isSourceClarificationResolved($entry)) {
                $eligible[] = $number;
            }
        }

        sort($eligible);

        return $eligible;
    }

    /**
     * @return list<array{candidate_id: string, number: int, reason: string}>
     */
    public function withheldCandidates(): array
    {
        $catalog = $this->catalog();
        $revisions = app(OriginalKarimenPilotSourceRevisionService::class);
        $withheld = [];

        foreach ($catalog['withheld'] as $row) {
            $number = (int) $row['number'];
            $entry = $this->findStagedEntry($number);

            if ($entry !== null && $revisions->isSourceClarificationResolved($entry)) {
                continue;
            }

            $withheld[] = [
                'candidate_id' => (string) $row['candidate_id'],
                'number' => $number,
                'reason' => (string) $row['reason'],
            ];
        }

        return $withheld;
    }

    public function sourceContentFingerprint(QuestionImportEntry $entry): string
    {
        return app(OriginalKarimenPilotAccuracyReviewService::class)->reviewContentFingerprint($entry);
    }

    public function isSourceStableForDrafting(QuestionImportEntry $entry): bool
    {
        if ($entry->source_group !== self::SOURCE_GROUP) {
            return false;
        }

        $entry->loadMissing(['translations', 'batch']);

        if ($entry->batch?->source_key !== self::COLLECTION_KEY) {
            return false;
        }

        $japanese = $entry->translationFor('ja');
        if ($japanese === null || ! filled($japanese->question_text) || ! filled($japanese->explanation)) {
            return false;
        }

        $decision = HumanAccuracyReviewDecision::tryFrom((string) data_get($entry->source_metadata, 'human_accuracy_decision', ''));
        if (in_array($decision, [
            HumanAccuracyReviewDecision::Rejected,
            HumanAccuracyReviewDecision::Deferred,
            HumanAccuracyReviewDecision::RevisionRequested,
        ], true)) {
            return false;
        }

        if ((bool) data_get($entry->source_metadata, 'human_accuracy_approval_stale')) {
            return false;
        }

        return true;
    }

    public function translationIsStale(QuestionImportEntry $entry, QuestionImportEntryTranslation $translation): bool
    {
        $stored = data_get($translation->japanese_reading_data, 'pilot_translation_meta.source_content_fingerprint');

        if (! is_string($stored) || $stored === '') {
            return true;
        }

        return ! hash_equals($stored, $this->sourceContentFingerprint($entry));
    }

    public function translationEligibleForPublication(QuestionImportEntry $entry, QuestionImportEntryTranslation $translation): bool
    {
        if ($translation->status === StagedTranslationStatus::ReadyForPublication
            && $translation->reviewed_at !== null
            && ! $this->translationIsStale($entry, $translation)
            && $entry->is_published) {
            return true;
        }

        return false;
    }

    /**
     * Refresh stale flags on existing pilot draft translations without overwriting text.
     */
    public function refreshStaleTranslationFlags(QuestionImportEntry $entry): void
    {
        if ($entry->source_group !== self::SOURCE_GROUP) {
            return;
        }

        $entry->loadMissing('translations');
        $current = $this->sourceContentFingerprint($entry);

        foreach ($entry->translations as $translation) {
            if ($translation->locale === 'ja') {
                continue;
            }

            if (! in_array($translation->locale, $this->targetLocales(), true)) {
                continue;
            }

            $meta = $translation->japanese_reading_data ?? [];
            $pilotMeta = is_array(data_get($meta, 'pilot_translation_meta'))
                ? data_get($meta, 'pilot_translation_meta')
                : [];
            $stored = data_get($pilotMeta, 'source_content_fingerprint');
            $isStale = ! is_string($stored) || $stored === '' || ! hash_equals($stored, $current);

            if (! $isStale && ! (bool) data_get($pilotMeta, 'source_version_stale')) {
                continue;
            }

            if (! $translation->status->allowsOverwriteWithoutForce() && ! $isStale) {
                continue;
            }

            $pilotMeta['source_version_stale'] = $isStale;
            $pilotMeta['native_reviewed'] = false;
            $meta['pilot_translation_meta'] = $pilotMeta;

            $flags = collect($translation->quality_flags ?? []);
            if ($isStale) {
                $flags = $flags->push('source_version_mismatch')->unique()->values();
            } else {
                $flags = $flags->reject(fn (string $flag): bool => $flag === 'source_version_mismatch')->values();
            }

            $status = $translation->status;
            if ($isStale && $status === StagedTranslationStatus::ReadyForPublication) {
                $status = StagedTranslationStatus::NeedsReview;
            }

            $translation->forceFill([
                'japanese_reading_data' => $meta,
                'quality_flags' => $flags->all(),
                'status' => $status,
                'reviewed_at' => $isStale ? null : $translation->reviewed_at,
            ])->save();
        }
    }

    /**
     * @return array{
     *     dry_run: bool,
     *     collection_key: string,
     *     eligible_count: int,
     *     withheld_count: int,
     *     created_count: int,
     *     updated_count: int,
     *     skipped_count: int,
     *     conflicted_count: int,
     *     protected_count: int,
     *     per_locale: array<string, int>,
     *     eligible_ids: list<string>,
     *     withheld: list<array{candidate_id: string, number: int, reason: string}>
     * }
     */
    public function stage(bool $dryRun = true): array
    {
        $catalog = $this->catalog();
        $eligibleNumbers = $this->eligibleCandidateNumbers();
        $withheld = $this->withheldCandidates();

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $conflicted = 0;
        $protected = 0;
        $perLocale = array_fill_keys($this->targetLocales(), 0);
        $eligibleIds = [];

        $apply = function () use (
            $catalog,
            $eligibleNumbers,
            $dryRun,
            &$created,
            &$updated,
            &$skipped,
            &$conflicted,
            &$protected,
            &$perLocale,
            &$eligibleIds,
        ): void {
            foreach ($eligibleNumbers as $number) {
                $entry = $this->findStagedEntry($number);
                if ($entry === null) {
                    $conflicted++;

                    continue;
                }

                $candidateId = (string) data_get($entry->source_metadata, 'candidate_id', $entry->stableSourceId());
                $eligibleIds[] = $candidateId;
                $fingerprint = $this->sourceContentFingerprint($entry);
                $localeBundle = $catalog['translations'][$candidateId] ?? null;

                if (! is_array($localeBundle)) {
                    $conflicted++;

                    continue;
                }

                foreach ($this->targetLocales() as $locale) {
                    $draft = $localeBundle[$locale] ?? null;
                    if (! is_array($draft)) {
                        $conflicted++;

                        continue;
                    }

                    $question = trim((string) ($draft['question_text'] ?? ''));
                    $explanation = trim((string) ($draft['explanation'] ?? ''));
                    $qualityFlags = $this->validateDraft($entry, $locale, $question, $explanation);

                    if ($question === '' || $explanation === '') {
                        $conflicted++;

                        continue;
                    }

                    $existing = QuestionImportEntryTranslation::query()
                        ->where('question_import_entry_id', $entry->id)
                        ->where('locale', $locale)
                        ->first();

                    if ($existing !== null && ! $existing->status->allowsOverwriteWithoutForce()) {
                        $protected++;
                        $skipped++;

                        continue;
                    }

                    if ($existing !== null
                        && filled($existing->translator_note)
                        && str_contains((string) $existing->translator_note, 'human-edited')
                        && $existing->status !== StagedTranslationStatus::Draft) {
                        $protected++;
                        $skipped++;

                        continue;
                    }

                    $status = $qualityFlags === []
                        ? StagedTranslationStatus::Draft
                        : StagedTranslationStatus::NeedsReview;

                    $meta = [
                        'pilot_translation_meta' => [
                            'candidate_id' => $candidateId,
                            'source_content_fingerprint' => $fingerprint,
                            'source_locale' => 'ja',
                            'drafting_method' => self::DRAFTING_METHOD,
                            'native_reviewed' => false,
                            'source_version_stale' => false,
                            'created_by_task' => 46,
                        ],
                    ];

                    if ($dryRun) {
                        $existing === null ? $created++ : $updated++;
                        $perLocale[$locale]++;

                        continue;
                    }

                    $model = QuestionImportEntryTranslation::query()->updateOrCreate(
                        [
                            'question_import_entry_id' => $entry->id,
                            'locale' => $locale,
                        ],
                        [
                            'question_text' => $question,
                            'explanation' => $explanation,
                            'figure_caption' => null,
                            'nuance_note' => 'Controlled AI-assisted draft for Japanese road-traffic learning. Not native-reviewed. Not publication-ready.',
                            'japanese_reading_data' => $meta,
                            'status' => $status,
                            'quality_flags' => $qualityFlags,
                            'translated_at' => now(),
                            'reviewed_at' => null,
                            'translator_note' => 'Task 46 private draft; drafting_method='.self::DRAFTING_METHOD.'; native_reviewed=false',
                        ],
                    );

                    if ($model->wasRecentlyCreated) {
                        $created++;
                    } else {
                        $updated++;
                    }
                    $perLocale[$locale]++;
                }

                if (! $dryRun) {
                    $metadata = $entry->source_metadata ?? [];
                    $metadata['translation_drafts_task_46'] = true;
                    $metadata['translation_reviewed'] = false;
                    $metadata['human_accuracy_review'] = (bool) data_get($metadata, 'human_accuracy_review', false);
                    $metadata['human_editorial_approval'] = false;
                    $metadata['public_exam_eligible'] = false;
                    $metadata['publication_eligible'] = false;

                    $entry->forceFill([
                        'source_metadata' => $metadata,
                        'is_published' => false,
                    ]);
                    $entry->publication_blockers = $entry->computePublicationBlockers();
                    $entry->save();
                }
            }
        };

        if ($dryRun) {
            $apply();
        } else {
            DB::transaction($apply);
        }

        return [
            'dry_run' => $dryRun,
            'collection_key' => self::COLLECTION_KEY,
            'eligible_count' => count($eligibleNumbers),
            'withheld_count' => count($withheld),
            'created_count' => $created,
            'updated_count' => $updated,
            'skipped_count' => $skipped,
            'conflicted_count' => $conflicted,
            'protected_count' => $protected,
            'per_locale' => $perLocale,
            'eligible_ids' => $eligibleIds,
            'withheld' => $withheld,
        ];
    }

    /**
     * @return list<string>
     */
    public function validateDraft(QuestionImportEntry $entry, string $locale, string $question, string $explanation): array
    {
        $flags = [];

        if (! SupportedLocale::isSupported($locale) || ! in_array($locale, $this->targetLocales(), true)) {
            $flags[] = 'wrong_language_code';
        }

        if ($question === '') {
            $flags[] = 'missing_translation_text';
        }

        if ($explanation === '') {
            $flags[] = 'missing_explanation';
        }

        $japanese = $entry->translationFor('ja');
        $jaQuestion = (string) ($japanese?->question_text ?? '');
        $jaExplanation = (string) ($japanese?->explanation ?? '');

        if ($this->numbersMissingFromSource($jaQuestion.' '.$jaExplanation, $question.' '.$explanation)) {
            $flags[] = 'potentially_altered_numbers';
        }

        if ($this->sourceHasNegation($jaQuestion) && ! $this->translationHasNegation($question)) {
            $flags[] = 'missing_negation';
        }

        if ($this->looksLikeUntranslatedJapanesePlaceholder($locale, $question)
            || $this->looksLikeUntranslatedJapanesePlaceholder($locale, $explanation)) {
            $flags[] = 'untranslated_japanese_placeholder';
        }

        if ($this->looksLikeEnglishPassthrough($locale, $question, (string) $entry->english_question)) {
            $flags[] = 'untranslated_english_question';
        }

        if ($this->impliesOppositeAnswer($entry, $question, $explanation)) {
            $flags[] = 'possible_answer_polarity_conflict';
        }

        return array_values(array_unique($flags));
    }

    private function findStagedEntry(int $number): ?QuestionImportEntry
    {
        return QuestionImportEntry::query()
            ->with(['translations', 'batch'])
            ->where('source_group', self::SOURCE_GROUP)
            ->where('original_question_number', $number)
            ->whereHas('batch', fn ($q) => $q->where('source_key', self::COLLECTION_KEY))
            ->first();
    }

    private function numbersMissingFromSource(string $source, string $translation): bool
    {
        preg_match_all('/\d+(?:\.\d+)?/', $source, $sourceNumbers);
        $sourceList = $sourceNumbers[0] ?? [];

        if ($sourceList === []) {
            return false;
        }

        preg_match_all('/\d+(?:\.\d+)?/', $translation, $translationNumbers);
        $translationList = $translationNumbers[0] ?? [];

        sort($sourceList);
        sort($translationList);

        return $sourceList !== $translationList;
    }

    private function sourceHasNegation(string $japanese): bool
    {
        return preg_match('/(ない|ません|ぬ|ず|禁止|不可|してはならない|入ってはならない)/u', $japanese) === 1;
    }

    private function translationHasNegation(string $translation): bool
    {
        return preg_match(
            '/(ない|ません|ぬ|ず|禁止|不可|なくても|must not|may not|cannot|should not|not |no |never|unnecessary|prohibited|forbidden|không|cấm|hindi|bawal|न|नाइ|ना|না|নেই|छैन|금지|안 |않|없어도|不是|不得|不能|不要|不许|不必)/iu',
            $translation
        ) === 1;
    }

    private function looksLikeUntranslatedJapanesePlaceholder(string $locale, string $text): bool
    {
        if (in_array($locale, ['zh', 'ko'], true)) {
            return false;
        }

        if (str_contains($text, 'TODO') || str_contains($text, '[TRANSLATE') || str_contains($text, 'FIXME')) {
            return true;
        }

        // Long unbroken Japanese runs in Latin-script locales are suspicious.
        if (in_array($locale, ['en', 'vi', 'fil', 'ne', 'bn'], true)
            && preg_match('/[\x{3040}-\x{30ff}\x{4e00}-\x{9fff}]{40,}/u', $text) === 1) {
            return true;
        }

        return false;
    }

    private function looksLikeEnglishPassthrough(string $locale, string $text, string $englishGloss): bool
    {
        if ($locale === 'en' || $englishGloss === '') {
            return false;
        }

        $a = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $text) ?? $text));
        $b = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $englishGloss) ?? $englishGloss));

        return $a !== '' && $a === $b;
    }

    private function impliesOppositeAnswer(QuestionImportEntry $entry, string $question, string $explanation): bool
    {
        $bundle = mb_strtolower($question.' '.$explanation);
        $saysFalse = preg_match('/\b(false|incorrect|wrong|誤り|잘못|sai|mali|गलत|ভুল|错误|不正确)\b/iu', $bundle) === 1
            || str_contains($bundle, '誤りです')
            || str_contains($bundle, 'this statement is false')
            || str_contains($bundle, 'the statement is false');
        $saysTrue = preg_match('/\b(true|correct|正しい|맞다|đúng|tama|सही|সঠিক|正确)\b/iu', $bundle) === 1;

        if ($entry->correct_answer === true && $saysFalse && ! $saysTrue) {
            return true;
        }

        if ($entry->correct_answer === false && $saysTrue && ! $saysFalse
            && ! str_contains($bundle, 'must not')
            && ! str_contains($bundle, 'may not')) {
            // False stems often explain "this is incorrect"; requiring saysFalse is enough.
            return ! $saysFalse;
        }

        return false;
    }

    /**
     * @return Collection<int, QuestionImportEntry>
     */
    public function stagedPilotEntries(): Collection
    {
        return QuestionImportEntry::query()
            ->with(['translations', 'batch'])
            ->where('source_group', self::SOURCE_GROUP)
            ->whereHas('batch', fn ($q) => $q->where('source_key', self::COLLECTION_KEY))
            ->orderBy('original_question_number')
            ->get();
    }
}
