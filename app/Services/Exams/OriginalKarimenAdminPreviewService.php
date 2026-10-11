<?php

namespace App\Services\Exams;

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamAttemptType;
use App\Enums\ExamScoringMode;
use App\Enums\QuestionType;
use App\Exceptions\Exams\InsufficientExamQuestionsException;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptQuestion;
use App\Models\ExamType;
use App\Models\QuestionImportEntry;
use App\Models\User;
use App\Services\Editorial\OriginalKarimenPilotAccuracyReviewService;
use App\Services\Editorial\OriginalKarimenPilotSourceRevisionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Private administrator preview of the staged original Japanese Karimen 50.
 *
 * Reuses ExamAttempt / ExamRunner scoring and locking. Never publishes, never
 * counts toward learner analytics, and never loads imported English banks.
 */
class OriginalKarimenAdminPreviewService
{
    public const REQUIRED_COUNT = 50;

    public const CLARIFICATION_CANDIDATE_NUMBERS = [1, 8, 26, 34, 49];

    public function __construct(
        private OriginalKarimenPilotAccuracyReviewService $reviews,
        private QuestionSelectionService $selection,
        private ExamAttemptService $attempts,
    ) {}

    /**
     * @return Collection<int, QuestionImportEntry>
     */
    public function stagedCandidates(): Collection
    {
        return $this->reviews->originalEntriesQuery()
            ->with(['translations', 'batch'])
            ->orderBy('original_question_number')
            ->orderBy('id')
            ->get()
            ->values();
    }

    /**
     * @return array{
     *     ready: bool,
     *     count: int,
     *     missing_ids: list<string>,
     *     clarification_ids: list<string>,
     *     incomplete_ids: list<string>
     * }
     */
    public function readiness(): array
    {
        $entries = $this->stagedCandidates();
        $missingIds = [];
        $incompleteIds = [];
        $clarificationIds = [];
        $seenNumbers = [];

        for ($number = 1; $number <= self::REQUIRED_COUNT; $number++) {
            $entry = $entries->firstWhere('original_question_number', $number);

            if ($entry === null) {
                $missingIds[] = $this->candidateIdForNumber($number);

                continue;
            }

            $seenNumbers[] = $number;
            $candidateId = (string) data_get($entry->source_metadata, 'candidate_id', $entry->stableSourceId());
            $japanese = $entry->translationFor('ja');

            if (
                blank($japanese?->question_text)
                || $entry->correct_answer === null
                || blank($japanese?->explanation)
                || blank($entry->answer_key_reference)
                || blank(data_get($entry->source_metadata, 'research_assessment'))
            ) {
                $incompleteIds[] = $candidateId;
            }

            if ($this->isClarificationCandidate($entry)) {
                $clarificationIds[] = $candidateId;
            }
        }

        $duplicates = $entries
            ->groupBy('original_question_number')
            ->filter(fn (Collection $group): bool => $group->count() > 1)
            ->keys()
            ->map(fn ($number): string => $this->candidateIdForNumber((int) $number))
            ->values()
            ->all();

        return [
            'ready' => count($seenNumbers) === self::REQUIRED_COUNT
                && $missingIds === []
                && $incompleteIds === []
                && $duplicates === [],
            'count' => $entries->count(),
            'missing_ids' => $missingIds,
            'clarification_ids' => $clarificationIds,
            'incomplete_ids' => array_values(array_unique([...$incompleteIds, ...$duplicates])),
        ];
    }

    public function assertCanPreview(User $user): void
    {
        if (! $user->isAdministrator() || $user->email_verified_at === null) {
            throw ValidationException::withMessages([
                'preview' => 'Only verified administrators may run the private Karimen preview.',
            ]);
        }
    }

    public function openAttempt(User $user): ?ExamAttempt
    {
        $examType = $this->karimenExamType();

        return ExamAttempt::query()
            ->whereBelongsTo($user)
            ->whereBelongsTo($examType)
            ->where('attempt_type', ExamAttemptType::AdminPreview)
            ->where('status', ExamAttemptStatus::InProgress)
            ->where('expires_at', '>', now())
            ->orderByDesc('id')
            ->first();
    }

    public function start(User $user, bool $forceRestart = false): ExamAttempt
    {
        $this->assertCanPreview($user);

        $readiness = $this->readiness();
        if (! $readiness['ready']) {
            throw new InsufficientExamQuestionsException(
                'Private Karimen preview requires exactly '.self::REQUIRED_COUNT
                .' complete original Japanese candidates. Found '.$readiness['count'].'.'
            );
        }

        return DB::transaction(function () use ($user, $forceRestart): ExamAttempt {
            $examType = $this->karimenExamType();
            $existing = ExamAttempt::query()
                ->whereBelongsTo($user)
                ->whereBelongsTo($examType)
                ->where('attempt_type', ExamAttemptType::AdminPreview)
                ->where('status', ExamAttemptStatus::InProgress)
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            if ($existing !== null) {
                if ($existing->expires_at->lte(now())) {
                    $this->attempts->timeout($existing);
                } elseif (! $forceRestart) {
                    return $this->attempts->syncProgress($existing)->loadForDisplay();
                } else {
                    $this->attempts->submit($existing);
                }
            }

            $entries = $this->stagedCandidates();
            if ($entries->count() !== self::REQUIRED_COUNT) {
                throw new InsufficientExamQuestionsException(
                    'Private Karimen preview expected '.self::REQUIRED_COUNT.' candidates.'
                );
            }

            $shuffled = $this->selection->shuffleAttemptOrder($entries);
            $startedAt = now();
            $maxScore = self::REQUIRED_COUNT * $examType->pointsPerStandardQuestion();

            $attempt = ExamAttempt::query()->create([
                'user_id' => $user->id,
                'exam_type_id' => $examType->id,
                'attempt_type' => ExamAttemptType::AdminPreview,
                'status' => ExamAttemptStatus::InProgress,
                'started_at' => $startedAt,
                'expires_at' => $startedAt->copy()->addMinutes(max(1, $examType->duration_minutes)),
                'question_count' => self::REQUIRED_COUNT,
                'max_score' => $maxScore,
                'passing_score' => min($examType->passing_score, $maxScore),
                'current_position' => 1,
                'counts_toward_analytics' => false,
                'locale' => 'ja',
            ]);

            foreach ($shuffled->values() as $index => $entry) {
                /** @var QuestionImportEntry $entry */
                ExamAttemptQuestion::query()->create([
                    'exam_attempt_id' => $attempt->id,
                    'question_id' => null,
                    'question_uuid' => (string) Str::uuid(),
                    'position' => $index + 1,
                    'question_type' => QuestionType::StandardTrueFalse,
                    'scoring_mode' => ExamScoringMode::Standard,
                    'scoring_weight' => $examType->pointsPerStandardQuestion(),
                    'content_snapshot' => $this->snapshotFromEntry($entry),
                ]);
            }

            return $attempt->loadForDisplay();
        });
    }

    public function isClarificationCandidate(QuestionImportEntry $entry): bool
    {
        $number = (int) $entry->original_question_number;

        if (in_array($number, self::CLARIFICATION_CANDIDATE_NUMBERS, true)) {
            return true;
        }

        return data_get($entry->source_metadata, 'research_assessment') === 'supported_with_clarification'
            || in_array('needs_editorial_clarification', $entry->review_flags ?? [], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshotFromEntry(QuestionImportEntry $entry): array
    {
        $japanese = $entry->translationFor('ja');
        $candidateId = (string) data_get($entry->source_metadata, 'candidate_id', $entry->stableSourceId());
        $needsClarification = $this->isClarificationCandidate($entry);
        $unresolvedRevision = app(OriginalKarimenPilotSourceRevisionService::class)->hasProposal($entry)
            && ! app(OriginalKarimenPilotSourceRevisionService::class)->isSourceClarificationResolved($entry);

        return [
            'japanese_text' => (string) ($japanese?->question_text ?? ''),
            'japanese_reading_data' => $japanese?->japanese_reading_data,
            'explanation_ja' => (string) ($japanese?->explanation ?? ''),
            'correct_answer' => (bool) $entry->correct_answer,
            'category_id' => null,
            'category_name' => (string) (data_get($entry->source_metadata, 'subject_ja') ?: $entry->legal_topic ?: 'Original Karimen'),
            'category_slug' => 'original-karimen-preview',
            'is_demo' => false,
            'media' => [],
            'translations' => [
                'ja' => [
                    'question_text' => (string) ($japanese?->question_text ?? ''),
                    'explanation' => (string) ($japanese?->explanation ?? ''),
                    'nuance_note' => null,
                ],
            ],
            'statements' => [],
            'admin_preview' => [
                'private' => true,
                'provisional_answers' => true,
                'candidate_id' => $candidateId,
                'stable_source_id' => $entry->stableSourceId(),
                'import_entry_id' => $entry->id,
                'research_assessment' => data_get($entry->source_metadata, 'research_assessment'),
                'needs_clarification' => $needsClarification,
                'unresolved_source_revision' => $unresolvedRevision,
                'notice_ja' => '管理者用・未承認問題の試験プレビュー',
            ],
        ];
    }

    private function karimenExamType(): ExamType
    {
        $examType = ExamType::query()->where('slug', 'karimen')->where('is_active', true)->first();

        if ($examType === null) {
            throw new InsufficientExamQuestionsException('Karimen exam type is not configured.');
        }

        return $examType;
    }

    private function candidateIdForNumber(int $number): string
    {
        if ($number <= 10) {
            return OriginalKarimenPilotAccuracyReviewService::SOURCE_GROUP.':'.$number;
        }

        return OriginalKarimenPilotAccuracyReviewService::EXPANSION_SOURCE_GROUP.':'.$number;
    }
}
