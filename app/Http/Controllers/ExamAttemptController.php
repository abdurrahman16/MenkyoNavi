<?php

namespace App\Http\Controllers;

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamAttemptType;
use App\Exceptions\Exams\InsufficientExamQuestionsException;
use App\Http\Requests\StartExamAttemptRequest;
use App\Models\ExamAttempt;
use App\Models\ExamType;
use App\Services\Exams\ExamAttemptService;
use App\Services\Exams\ExamQuestionPresenter;
use App\Services\Exams\ExamScoringService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExamAttemptController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private ExamAttemptService $attempts,
        private ExamScoringService $scoring,
        private ExamQuestionPresenter $presenter,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', ExamAttempt::class);

        $attempts = ExamAttempt::query()
            ->whereBelongsTo(auth()->user())
            ->where('attempt_type', '!=', ExamAttemptType::AdminPreview)
            ->with('examType')
            ->orderByDesc('id')
            ->paginate(15);

        return view('exams.attempts.index', [
            'attempts' => $attempts,
        ]);
    }

    public function store(StartExamAttemptRequest $request, ExamType $examType): RedirectResponse
    {
        abort_unless($examType->is_active, 404);

        try {
            $attempt = $this->attempts->start(
                $request->user(),
                $examType,
                $request->enum('attempt_type', ExamAttemptType::class),
            );
        } catch (InsufficientExamQuestionsException $exception) {
            return back()->with('exam_error', $exception->getMessage());
        }

        return redirect()->route('exams.attempts.show', $attempt);
    }

    public function show(ExamAttempt $attempt): View|RedirectResponse
    {
        $this->authorize('view', $attempt);

        if ($attempt->status !== ExamAttemptStatus::InProgress) {
            return redirect()->route('exams.attempts.results', $attempt);
        }

        if ($attempt->expires_at->lte(now())) {
            $this->attempts->timeout($attempt);

            return redirect()
                ->route('exams.attempts.results', $attempt)
                ->with('exam_notice', __('exams.time_expired'));
        }

        $attempt = $this->attempts->syncProgress($attempt);

        return view('exams.attempts.show', [
            'attempt' => $attempt->load('examType'),
        ]);
    }

    public function submit(ExamAttempt $attempt): RedirectResponse
    {
        $this->authorize('view', $attempt);

        $final = $this->attempts->submit($attempt);
        $notice = $final->status === ExamAttemptStatus::TimedOut
            ? __('exams.time_expired')
            : null;

        return redirect()
            ->route('exams.attempts.results', $final)
            ->with('exam_notice', $notice);
    }

    public function results(ExamAttempt $attempt): View|RedirectResponse
    {
        $this->authorize('view', $attempt);

        if ($attempt->status === ExamAttemptStatus::InProgress) {
            if ($attempt->expires_at->lte(now())) {
                $attempt = $this->attempts->timeout($attempt);
            } else {
                return redirect()->route('exams.attempts.show', $attempt);
            }
        }

        $attempt->loadForDisplay();
        $locale = $this->presenter->preferredLocale();
        $reviewed = $attempt->questions
            ->map(fn ($question): array => $this->presenter->forReview($question, $locale));

        return view('exams.attempts.results', [
            'attempt' => $attempt,
            'counts' => $this->scoring->outcomeCounts($attempt),
            'categories' => $this->scoring->categoryBreakdown($attempt),
            'incorrectQuestions' => $reviewed
                ->filter(fn (array $question): bool => ! $question['is_correct'] && $this->questionHasAnyAnswer($question))
                ->values()
                ->all(),
            'unansweredQuestions' => $reviewed
                ->filter(fn (array $question): bool => ! $this->questionHasAnyAnswer($question))
                ->values()
                ->all(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $question
     */
    private function questionHasAnyAnswer(array $question): bool
    {
        if ($question['illustrated'] ?? false) {
            return collect($question['statements'] ?? [])->contains(
                fn (array $statement): bool => ($statement['selected'] ?? null) !== null
            );
        }

        return ($question['selected'] ?? null) !== null;
    }

    public function review(ExamAttempt $attempt): View|RedirectResponse
    {
        $this->authorize('view', $attempt);

        if ($attempt->status === ExamAttemptStatus::InProgress) {
            return redirect()->route('exams.attempts.show', $attempt);
        }

        abort_unless($attempt->status->revealsAnswers(), 403);

        $attempt->loadForDisplay();
        $locale = $this->presenter->preferredLocale();

        $questions = $attempt->questions
            ->map(fn ($question): array => $this->presenter->forReview($question, $locale))
            ->all();

        return view('exams.attempts.review', [
            'attempt' => $attempt,
            'questions' => $questions,
        ]);
    }
}
