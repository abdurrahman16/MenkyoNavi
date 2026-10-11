<?php

namespace App\Livewire;

use App\Enums\ExamAttemptStatus;
use App\Exceptions\Exams\AnswerLockedException;
use App\Exceptions\Exams\ExamClosedException;
use App\Exceptions\Exams\InvalidExamProgressException;
use App\Models\ExamAttempt;
use App\Services\Exams\ExamAttemptService;
use App\Services\Exams\ExamQuestionPresenter;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ExamRunner extends Component
{
    public int $attemptId;

    public bool $showSubmitModal = false;

    public ?string $answerError = null;

    public bool $awaitingGroupConfirm = false;

    public function mount(ExamAttempt $attempt, ExamAttemptService $attempts): void
    {
        $this->authorize('view', $attempt);
        $this->attemptId = $attempt->id;
        $this->expireIfNeeded($attempt, $attempts);

        if ($attempt->status === ExamAttemptStatus::InProgress) {
            $synced = $attempts->syncProgress($attempt);
            $this->awaitingGroupConfirm = $this->shouldAwaitGroupConfirm($synced);
            $this->showSubmitModal = false;

            if ($this->allUnitsAnswered($synced)) {
                $this->showSubmitModal = true;
            }
        }
    }

    public function selectAnswer(int $attemptQuestionId, int $statementSlot, bool $selected, ExamAttemptService $attempts): void
    {
        $this->answerError = null;
        $attempt = $this->attempt();
        $this->authorize('view', $attempt);

        if ($this->expireIfNeeded($attempt, $attempts)) {
            return;
        }

        $this->authorize('update', $attempt);

        try {
            $result = $attempts->recordAnswer($attempt, $attemptQuestionId, $statementSlot, $selected);
        } catch (ExamClosedException) {
            $fresh = $attempt->fresh() ?? $attempt;
            $this->redirectToResults($fresh, __('exams.time_expired'));

            return;
        } catch (AnswerLockedException|InvalidExamProgressException $exception) {
            $this->answerError = $exception->getMessage();

            return;
        } catch (ValidationException $exception) {
            $this->answerError = collect($exception->errors())->flatten()->first()
                ?? __('exams.error_save_failed');

            return;
        }

        $this->awaitingGroupConfirm = $result['illustrated'] && $result['unit_complete'] && ! $result['advanced'];

        if ($result['ready_to_submit']) {
            $this->showSubmitModal = true;
            $this->awaitingGroupConfirm = false;
        }
    }

    public function confirmIllustratedGroup(int $attemptQuestionId, ExamAttemptService $attempts): void
    {
        $this->answerError = null;
        $attempt = $this->attempt();
        $this->authorize('view', $attempt);

        if ($this->expireIfNeeded($attempt, $attempts)) {
            return;
        }

        $this->authorize('update', $attempt);

        try {
            $result = $attempts->confirmIllustratedGroup($attempt, $attemptQuestionId);
        } catch (ExamClosedException) {
            $this->redirectToResults($attempt->fresh() ?? $attempt, __('exams.time_expired'));

            return;
        } catch (InvalidExamProgressException $exception) {
            $this->answerError = $exception->getMessage();

            return;
        }

        $this->awaitingGroupConfirm = false;

        if ($result['ready_to_submit']) {
            $this->showSubmitModal = true;
        }
    }

    public function openSubmitModal(): void
    {
        $this->showSubmitModal = true;
    }

    public function closeSubmitModal(): void
    {
        $this->showSubmitModal = false;
    }

    public function submitExam(ExamAttemptService $attempts): void
    {
        $attempt = $this->attempt();
        $this->authorize('view', $attempt);
        $final = $attempts->submit($attempt);
        $notice = $final->status === ExamAttemptStatus::TimedOut
            ? __('exams.time_expired')
            : null;

        $this->redirectToResults($final, $notice);
    }

    public function closeIfExpired(ExamAttemptService $attempts): void
    {
        $attempt = $this->attempt();

        if ($attempt->status !== ExamAttemptStatus::InProgress) {
            $this->redirectToResults($attempt);

            return;
        }

        if ($attempt->expires_at->lte(now())) {
            $final = $attempts->timeout($attempt);
            $this->redirectToResults($final, __('exams.time_expired'));
        }
    }

    public function render(ExamQuestionPresenter $presenter)
    {
        $attempt = $this->attempt()->loadForDisplay();
        $locale = $presenter->preferredLocale();
        $current = $attempt->questions->firstWhere('position', $attempt->current_position)
            ?? $attempt->questions->first();
        $exitUrl = $attempt->isAdminPreview()
            ? route('admin.questions.original-karimen-exam-preview')
            : (Route::has('exams.'.$attempt->examType->slug)
                ? route('exams.'.$attempt->examType->slug)
                : route('exams.index'));
        $answeredCount = $attempt->questions->filter(
            fn ($question): bool => $presenter->questionIsAnswered($question)
        )->count();
        $unansweredCount = max(0, $attempt->question_count - $answeredCount);
        $allAnswered = $unansweredCount === 0;
        $currentPresented = $current ? $presenter->forExam($current, $locale) : null;
        $groupReady = $currentPresented !== null
            && $currentPresented['illustrated']
            && $currentPresented['answered'];

        return view('livewire.exam-runner', [
            'attempt' => $attempt,
            'current' => $currentPresented,
            'palette' => $attempt->questions->map(fn ($question): array => [
                'position' => $question->position,
                'answered' => $presenter->questionIsAnswered($question),
                'current' => (int) $question->position === (int) $attempt->current_position && ! $allAnswered,
            ])->all(),
            'answeredCount' => $answeredCount,
            'unansweredCount' => $unansweredCount,
            'allAnswered' => $allAnswered,
            'groupReady' => $groupReady,
            'exitUrl' => $exitUrl,
            'expiresAtMs' => $attempt->expires_at->getTimestamp() * 1000,
            'serverNowMs' => now()->getTimestamp() * 1000,
            'locale' => $locale,
            'totalSeconds' => max(1, $attempt->expires_at->getTimestamp() - $attempt->started_at->getTimestamp()),
        ]);
    }

    private function attempt(): ExamAttempt
    {
        $userId = auth()->id();

        if ($userId === null) {
            abort(404);
        }

        return ExamAttempt::query()
            ->where('user_id', $userId)
            ->with('examType')
            ->findOrFail($this->attemptId);
    }

    private function expireIfNeeded(ExamAttempt $attempt, ExamAttemptService $attempts): bool
    {
        if ($attempt->status !== ExamAttemptStatus::InProgress) {
            $this->redirectToResults($attempt);

            return true;
        }

        if ($attempt->expires_at->lte(now())) {
            $final = $attempts->timeout($attempt);
            $this->redirectToResults($final, __('exams.time_expired'));

            return true;
        }

        return false;
    }

    private function redirectToResults(ExamAttempt $attempt, ?string $notice = null): void
    {
        if (filled($notice)) {
            session()->flash('exam_notice', $notice);
        }

        $this->redirect(route('exams.attempts.results', $attempt), navigate: false);
    }

    private function shouldAwaitGroupConfirm(ExamAttempt $attempt): bool
    {
        $attempt->loadMissing(['questions.answers']);
        $current = $attempt->questions->firstWhere('position', (int) $attempt->current_position);

        if ($current === null || ! $current->isIllustratedGroup()) {
            return false;
        }

        $expected = count($current->content_snapshot['statements'] ?? []);

        return $expected > 0 && $current->answers->count() >= $expected;
    }

    private function allUnitsAnswered(ExamAttempt $attempt): bool
    {
        $attempt->loadMissing(['questions.answers']);

        return $attempt->questions->every(function ($question): bool {
            if ($question->isIllustratedGroup()) {
                $expected = count($question->content_snapshot['statements'] ?? []);

                return $expected > 0 && $question->answers->count() >= $expected;
            }

            return $question->answers->contains(fn ($answer): bool => (int) $answer->statement_slot === 0);
        });
    }
}
