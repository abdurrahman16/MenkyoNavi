<?php

namespace App\Services\Exams;

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamAttemptType;
use App\Enums\ExamScoringMode;
use App\Enums\QuestionType;
use App\Exceptions\Exams\AnswerLockedException;
use App\Exceptions\Exams\ExamClosedException;
use App\Exceptions\Exams\InsufficientExamQuestionsException;
use App\Exceptions\Exams\InvalidExamProgressException;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamAttemptQuestion;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\User;
use App\Support\LocalePreference;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class ExamAttemptService
{
    public function __construct(
        private QuestionSelectionService $selection,
        private ExamScoringService $scoring,
        private MistakeRecorder $mistakes,
        private LocalePreference $locales,
    ) {}

    public function start(User $user, ExamType $examType, ExamAttemptType $type): ExamAttempt
    {
        if ($type === ExamAttemptType::AdminPreview) {
            throw new InsufficientExamQuestionsException(
                'Admin preview attempts must be started through the private Karimen preview workflow.'
            );
        }

        if ($type === ExamAttemptType::Demo && ! $this->selection->demoModeEnabled()) {
            throw new InsufficientExamQuestionsException(
                'Demo practice is not available in this environment.'
            );
        }

        return DB::transaction(function () use ($user, $examType, $type): ExamAttempt {
            $existing = ExamAttempt::query()
                ->whereBelongsTo($user)
                ->whereBelongsTo($examType)
                ->where('attempt_type', $type)
                ->where('status', ExamAttemptStatus::InProgress)
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            if ($existing !== null) {
                if ($existing->expires_at->lte(now())) {
                    $this->finalizeLocked($existing, ExamAttemptStatus::TimedOut);
                } else {
                    return $this->syncProgressLocked($existing)->loadForDisplay();
                }
            }

            $questions = $this->selection->select($examType, $type, $this->preferredLocale());
            $maxScore = $questions->sum(fn (Question $question): int => $this->weightFor($examType, $question));
            $passingScore = $type === ExamAttemptType::Demo
                ? $this->demoPassingScore($examType, $maxScore)
                : min($examType->passing_score, $maxScore);
            $startedAt = now();

            $attempt = ExamAttempt::query()->create([
                'user_id' => $user->id,
                'exam_type_id' => $examType->id,
                'attempt_type' => $type,
                'status' => ExamAttemptStatus::InProgress,
                'started_at' => $startedAt,
                'expires_at' => $startedAt->copy()->addMinutes(max(1, $examType->duration_minutes)),
                'question_count' => $questions->count(),
                'max_score' => $maxScore,
                'passing_score' => $passingScore,
                'current_position' => 1,
                'counts_toward_analytics' => $type === ExamAttemptType::Full,
                'locale' => $this->preferredLocale(),
            ]);

            foreach ($questions->values() as $index => $question) {
                ExamAttemptQuestion::query()->create([
                    'exam_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'question_uuid' => $question->uuid,
                    'position' => $index + 1,
                    'question_type' => $question->type,
                    'scoring_mode' => $question->isIllustratedGroup()
                        ? ExamScoringMode::AllOrNothing
                        : ExamScoringMode::Standard,
                    'scoring_weight' => $this->weightFor($examType, $question),
                    'content_snapshot' => $this->snapshot($question),
                ]);
            }

            return $attempt->loadForDisplay();
        });
    }

    /**
     * Resume one-way progress at the earliest unanswered unit (compatible with pre-Task-25 attempts).
     */
    public function syncProgress(ExamAttempt $attempt): ExamAttempt
    {
        return DB::transaction(function () use ($attempt): ExamAttempt {
            $locked = $this->lock($attempt);

            if ($locked->status !== ExamAttemptStatus::InProgress) {
                return $locked->loadForDisplay();
            }

            return $this->syncProgressLocked($locked)->loadForDisplay();
        });
    }

    /**
     * Persist a single True/False selection for the current answerable unit.
     * Standard questions auto-advance after a successful save. Illustrated groups do not.
     *
     * @return array{saved: bool, advanced: bool, unit_complete: bool, ready_to_submit: bool, position: int, illustrated: bool}
     */
    public function recordAnswer(ExamAttempt $attempt, int $attemptQuestionId, int $statementSlot, bool $selected): array
    {
        return DB::transaction(function () use ($attempt, $attemptQuestionId, $statementSlot, $selected): array {
            $locked = $this->assertActive($attempt);
            $this->syncProgressLocked($locked);

            $target = $locked->questions()->with('answers')->whereKey($attemptQuestionId)->first();

            if ($target === null) {
                throw ValidationException::withMessages([
                    'question' => 'That question is not part of this examination.',
                ]);
            }

            if ($this->slotIsLocked($target, $statementSlot)) {
                throw new AnswerLockedException(__('exams.error_answer_locked'));
            }

            $question = $this->currentQuestion($locked);

            if ($question === null || (int) $question->id !== $attemptQuestionId) {
                throw new InvalidExamProgressException(__('exams.error_not_current_question'));
            }

            $statementId = $this->statementIdForSlot($question, $statementSlot);
            $isCorrect = $this->selectionMatchesSnapshot($question, $statementSlot, $selected);
            $points = $question->scoring_mode === ExamScoringMode::Standard && $isCorrect
                ? $question->scoring_weight
                : 0;

            try {
                ExamAttemptAnswer::query()->create([
                    'exam_attempt_question_id' => $question->id,
                    'statement_slot' => $statementSlot,
                    'question_statement_id' => $statementId,
                    'selected_answer' => $selected,
                    'is_correct' => $isCorrect,
                    'points_awarded' => $points,
                    'answered_at' => now(),
                ]);
            } catch (Throwable $exception) {
                if ($this->slotIsLocked($question->fresh(['answers']) ?? $question, $statementSlot)) {
                    throw new AnswerLockedException(__('exams.error_answer_locked'));
                }

                throw $exception;
            }

            $question->load('answers');
            $unitComplete = $this->unitIsComplete($question);
            $advanced = false;
            $readyToSubmit = false;

            if ($unitComplete && ! $question->isIllustratedGroup()) {
                [$advanced, $readyToSubmit] = $this->advanceAfterUnitComplete($locked, $question);
            } elseif ($unitComplete && $question->isIllustratedGroup()) {
                $readyToSubmit = false;
            }

            return [
                'saved' => true,
                'advanced' => $advanced,
                'unit_complete' => $unitComplete,
                'ready_to_submit' => $readyToSubmit || $this->allUnitsComplete($locked),
                'position' => (int) $locked->fresh()->current_position,
                'illustrated' => $question->isIllustratedGroup(),
            ];
        });
    }

    /**
     * Lock a fully answered illustrated group and advance to the next unit.
     *
     * @return array{advanced: bool, ready_to_submit: bool, position: int}
     */
    public function confirmIllustratedGroup(ExamAttempt $attempt, int $attemptQuestionId): array
    {
        return DB::transaction(function () use ($attempt, $attemptQuestionId): array {
            $locked = $this->assertActive($attempt);
            $this->syncProgressLocked($locked);

            $question = $this->currentQuestion($locked);

            if ($question === null || (int) $question->id !== $attemptQuestionId || ! $question->isIllustratedGroup()) {
                throw new InvalidExamProgressException(__('exams.error_not_current_question'));
            }

            $question->load('answers');

            if (! $this->unitIsComplete($question)) {
                throw new InvalidExamProgressException(__('exams.error_group_incomplete'));
            }

            [$advanced, $readyToSubmit] = $this->advanceAfterUnitComplete($locked, $question);

            return [
                'advanced' => $advanced,
                'ready_to_submit' => $readyToSubmit || $this->allUnitsComplete($locked),
                'position' => (int) $locked->fresh()->current_position,
            ];
        });
    }

    /**
     * Free navigation is disabled for one-way examinations.
     */
    public function move(ExamAttempt $attempt, int $position): void
    {
        throw new InvalidExamProgressException(__('exams.error_navigation_disabled'));
    }

    public function submit(ExamAttempt $attempt): ExamAttempt
    {
        return DB::transaction(function () use ($attempt): ExamAttempt {
            $locked = $this->lock($attempt);

            if ($locked->status !== ExamAttemptStatus::InProgress) {
                return $locked;
            }

            $withinGrace = now()->lte($locked->expires_at->copy()->addSeconds(5));
            $status = $withinGrace ? ExamAttemptStatus::Submitted : ExamAttemptStatus::TimedOut;

            return $this->finalizeLocked($locked, $status);
        });
    }

    public function timeout(ExamAttempt $attempt): ExamAttempt
    {
        return DB::transaction(function () use ($attempt): ExamAttempt {
            $locked = $this->lock($attempt);

            if ($locked->status !== ExamAttemptStatus::InProgress) {
                return $locked;
            }

            return $this->finalizeLocked($locked, ExamAttemptStatus::TimedOut);
        });
    }

    private function lock(ExamAttempt $attempt): ExamAttempt
    {
        return ExamAttempt::query()->whereKey($attempt->id)->lockForUpdate()->firstOrFail();
    }

    private function assertActive(ExamAttempt $attempt): ExamAttempt
    {
        $locked = $this->lock($attempt);

        if ($locked->status !== ExamAttemptStatus::InProgress) {
            throw new ExamClosedException;
        }

        if ($locked->expires_at->lte(now())) {
            $this->finalizeLocked($locked, ExamAttemptStatus::TimedOut);

            throw new ExamClosedException('The examination time has ended.');
        }

        return $locked;
    }

    private function syncProgressLocked(ExamAttempt $locked): ExamAttempt
    {
        $locked->load(['questions.answers']);

        $target = $this->earliestAnswerablePosition($locked);

        if ((int) $locked->current_position !== $target) {
            $locked->current_position = $target;
            $locked->save();
        }

        return $locked;
    }

    private function earliestAnswerablePosition(ExamAttempt $attempt): int
    {
        $questions = $attempt->questions->sortBy('position')->values();

        foreach ($questions as $question) {
            if (! $this->unitIsComplete($question)) {
                return max(1, (int) $question->position);
            }
        }

        return max(1, (int) $attempt->question_count);
    }

    private function currentQuestion(ExamAttempt $attempt): ?ExamAttemptQuestion
    {
        $attempt->loadMissing(['questions.answers']);

        return $attempt->questions->firstWhere('position', (int) $attempt->current_position);
    }

    private function slotIsLocked(ExamAttemptQuestion $question, int $statementSlot): bool
    {
        $question->loadMissing('answers');

        return $question->answers->contains(
            fn (ExamAttemptAnswer $answer): bool => (int) $answer->statement_slot === $statementSlot
        );
    }

    private function unitIsComplete(ExamAttemptQuestion $question): bool
    {
        $question->loadMissing('answers');

        if ($question->isIllustratedGroup()) {
            $expected = count($question->content_snapshot['statements'] ?? []);

            return $expected > 0 && $question->answers->count() >= $expected;
        }

        return $question->answers->contains(
            fn (ExamAttemptAnswer $answer): bool => (int) $answer->statement_slot === 0
        );
    }

    private function allUnitsComplete(ExamAttempt $attempt): bool
    {
        $attempt->loadMissing(['questions.answers']);

        return $attempt->questions->every(fn (ExamAttemptQuestion $question): bool => $this->unitIsComplete($question));
    }

    /**
     * @return array{0: bool, 1: bool} [advanced, ready_to_submit]
     */
    private function advanceAfterUnitComplete(ExamAttempt $locked, ExamAttemptQuestion $question): array
    {
        $next = $locked->questions
            ->where('position', '>', (int) $question->position)
            ->sortBy('position')
            ->first();

        if ($next === null) {
            return [false, true];
        }

        $locked->current_position = (int) $next->position;
        $locked->save();

        return [true, false];
    }

    private function finalizeLocked(ExamAttempt $locked, ExamAttemptStatus $status): ExamAttempt
    {
        if ($locked->status !== ExamAttemptStatus::InProgress) {
            return $locked;
        }

        $locked->load([
            'questions.answers',
        ]);

        $result = $this->scoring->score($locked);

        foreach ($result['questions'] as $row) {
            ExamAttemptQuestion::query()->whereKey($row['id'])->update([
                'points_awarded' => $row['points_awarded'],
                'is_correct' => $row['is_correct'],
            ]);
        }

        $allowedSeconds = max(0, $locked->expires_at->getTimestamp() - $locked->started_at->getTimestamp());
        $elapsedSeconds = min(
            $allowedSeconds,
            max(0, now()->getTimestamp() - $locked->started_at->getTimestamp()),
        );

        $locked->forceFill([
            'status' => $status,
            'submitted_at' => now(),
            'score' => $result['score'],
            'passed' => $result['passed'],
            'duration_seconds' => $elapsedSeconds,
        ])->save();

        $locked->refresh()->load(['questions.answers', 'questions.question']);
        $this->mistakes->recordFromAttempt($locked);

        return $locked;
    }

    private function weightFor(ExamType $examType, Question $question): int
    {
        if ($question->isIllustratedGroup()) {
            return $examType->pointsPerIllustratedGroup();
        }

        return $examType->pointsPerStandardQuestion();
    }

    private function demoPassingScore(ExamType $examType, int $demoMaxScore): int
    {
        if ($demoMaxScore < 1 || $examType->max_score < 1) {
            return 0;
        }

        $scaled = (int) ceil($demoMaxScore * ($examType->passing_score / $examType->max_score));

        return min($demoMaxScore, max(1, $scaled));
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(Question $question): array
    {
        return [
            'japanese_text' => $question->japanese_text,
            'japanese_reading_data' => $question->japanese_reading_data,
            'explanation_ja' => $question->explanation_ja,
            'correct_answer' => $question->isIllustratedGroup() ? null : $question->correct_answer,
            'category_id' => $question->question_category_id,
            'category_name' => $question->category?->name,
            'category_slug' => $question->category?->slug,
            'is_demo' => $question->is_demo,
            'media' => $question->media->map(fn ($media): array => [
                'disk' => $media->disk,
                'path' => $media->path,
                'alt_text' => $media->alt_text,
                'mime_type' => $media->mime_type,
            ])->values()->all(),
            'translations' => $question->translations->mapWithKeys(fn ($translation): array => [
                $translation->locale => [
                    'question_text' => $translation->question_text,
                    'explanation' => $translation->explanation,
                    'nuance_note' => $translation->nuance_note,
                ],
            ])->all(),
            'statements' => $question->statements->map(fn ($statement): array => [
                'id' => $statement->id,
                'sort_order' => $statement->sort_order,
                'japanese_text' => $statement->japanese_text,
                'japanese_reading_data' => $statement->japanese_reading_data,
                'correct_answer' => $statement->correct_answer,
                'explanation_ja' => $statement->explanation_ja,
                'translations' => $statement->translations->mapWithKeys(fn ($translation): array => [
                    $translation->locale => [
                        'question_text' => $translation->statement_text,
                        'explanation' => $translation->explanation,
                        'nuance_note' => $translation->nuance_note,
                    ],
                ])->all(),
            ])->values()->all(),
        ];
    }

    private function statementIdForSlot(ExamAttemptQuestion $question, int $statementSlot): ?int
    {
        if ($question->scoring_mode === ExamScoringMode::Standard) {
            if ($statementSlot !== 0) {
                throw ValidationException::withMessages([
                    'statement_slot' => 'This question accepts one true or false answer.',
                ]);
            }

            return null;
        }

        $statement = collect($question->content_snapshot['statements'] ?? [])
            ->first(fn (array $row): bool => (int) ($row['sort_order'] ?? 0) === $statementSlot);

        if (! is_array($statement)) {
            throw ValidationException::withMessages([
                'statement_slot' => 'That statement is not part of this question.',
            ]);
        }

        return isset($statement['id']) ? (int) $statement['id'] : null;
    }

    private function selectionMatchesSnapshot(ExamAttemptQuestion $question, int $statementSlot, bool $selected): bool
    {
        if ($question->question_type === QuestionType::IllustratedGroup || $question->scoring_mode === ExamScoringMode::AllOrNothing) {
            $statement = collect($question->content_snapshot['statements'] ?? [])
                ->first(fn (array $row): bool => (int) ($row['sort_order'] ?? 0) === $statementSlot);

            return is_array($statement) && $selected === (bool) ($statement['correct_answer'] ?? false);
        }

        return $selected === (bool) ($question->content_snapshot['correct_answer'] ?? false);
    }

    private function preferredLocale(): string
    {
        return $this->locales->normalize((string) session('locale', $this->locales->defaultLocale()))
            ?? $this->locales->defaultLocale();
    }
}
