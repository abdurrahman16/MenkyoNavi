<?php

namespace App\Services\Exams;

use App\Enums\ContentReviewStatus;
use App\Enums\ExamAttemptStatus;
use App\Enums\ExamAttemptType;
use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Exceptions\Exams\InsufficientExamQuestionsException;
use App\Models\ExamAttempt;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\User;
use App\Support\LocalePreference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class QuestionSelectionService
{
    public function __construct(public LocalePreference $locales) {}

    public function demoModeEnabled(): bool
    {
        $configured = config('exams.demo_enabled');

        if ($configured === null || $configured === '') {
            return app()->environment('local', 'testing');
        }

        return filter_var($configured, FILTER_VALIDATE_BOOLEAN);
    }

    public function fullAvailability(ExamType $examType, ?string $locale = null): ExamPoolAvailability
    {
        return $this->availability($examType, ExamAttemptType::Full, $locale);
    }

    public function demoAvailability(ExamType $examType, ?string $locale = null): ExamPoolAvailability
    {
        $availability = $this->availability($examType, ExamAttemptType::Demo, $locale);

        if (! $this->demoModeEnabled()) {
            return new ExamPoolAvailability(
                canStart: false,
                availableStandard: $availability->availableStandard,
                availableIllustrated: $availability->availableIllustrated,
                requiredStandard: $availability->requiredStandard,
                requiredIllustrated: $availability->requiredIllustrated,
                questionCount: 0,
                maxScore: 0,
                message: __('exams.demo_not_available'),
            );
        }

        return $availability;
    }

    /**
     * @return Collection<int, Question>
     */
    public function select(ExamType $examType, ExamAttemptType $type, ?string $locale = null): Collection
    {
        $locale = $this->normalizeContentLocale($locale);
        $availability = $type === ExamAttemptType::Demo
            ? $this->demoAvailability($examType, $locale)
            : $this->fullAvailability($examType, $locale);

        if (! $availability->canStart) {
            throw new InsufficientExamQuestionsException(
                $availability->message ?? __('exams.practice_not_configured')
            );
        }

        $standard = $this->draw(
            $examType,
            QuestionType::StandardTrueFalse,
            $type,
            $type === ExamAttemptType::Demo ? $availability->availableStandard : $availability->requiredStandard,
            $locale,
        );

        $illustrated = $this->draw(
            $examType,
            QuestionType::IllustratedGroup,
            $type,
            $type === ExamAttemptType::Demo ? $availability->availableIllustrated : $availability->requiredIllustrated,
            $locale,
        );

        // Shuffle whole-question units once for this attempt. Illustrated groups stay
        // intact as single Question models; statement order remains in content_snapshot.
        return $this->shuffleAttemptOrder($standard->concat($illustrated));
    }

    /**
     * Randomize question order for a new attempt only. Persistence is handled by
     * ExamAttemptService writing exam_attempt_questions.position — never reshuffle later.
     *
     * @param  Collection<int, Question>  $questions
     * @return Collection<int, Question>
     */
    public function shuffleAttemptOrder(Collection $questions): Collection
    {
        return $questions->values()->shuffle()->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function practiceOffer(string $slug, ?User $user, ?string $locale = null): array
    {
        $locale = $this->normalizeContentLocale($locale);
        $examType = ExamType::query()->where('slug', $slug)->where('is_active', true)->first();

        if ($examType === null) {
            return [
                'configured' => false,
                'message' => __('exams.practice_not_configured'),
            ];
        }

        $full = $this->fullAvailability($examType, $locale);
        $demo = $this->demoAvailability($examType, $locale);
        $resume = [
            'demo' => null,
            'full' => null,
        ];

        if ($user !== null) {
            $openAttempts = ExamAttempt::query()
                ->whereBelongsTo($user)
                ->whereBelongsTo($examType)
                ->whereIn('attempt_type', [ExamAttemptType::Demo, ExamAttemptType::Full])
                ->where('status', ExamAttemptStatus::InProgress)
                ->where('expires_at', '>', now())
                ->orderByDesc('id')
                ->get();

            foreach ($openAttempts as $attempt) {
                $resume[$attempt->attempt_type->value] ??= $attempt->uuid;
            }
        }

        return [
            'configured' => true,
            'slug' => $examType->slug,
            'name' => $examType->name,
            'name_ja' => $examType->name_ja,
            'duration_minutes' => $examType->duration_minutes,
            'max_score' => $examType->max_score,
            'passing_score' => $examType->passing_score,
            'standard_count' => $examType->standardQuestionCount(),
            'illustrated_count' => $examType->illustratedGroupCount(),
            'content_locale' => $locale,
            'demo_enabled' => $this->demoModeEnabled(),
            'full' => [
                'can_start' => $full->canStart,
                'question_count' => $full->requiredStandard + $full->requiredIllustrated,
                'message' => $full->message,
                'available_standard' => $full->availableStandard,
                'available_illustrated' => $full->availableIllustrated,
                'required_standard' => $full->requiredStandard,
                'required_illustrated' => $full->requiredIllustrated,
            ],
            'demo' => [
                'can_start' => $demo->canStart,
                'question_count' => $demo->questionCount,
                'max_score' => $demo->maxScore,
                'message' => $demo->message,
            ],
            'resume' => $resume,
        ];
    }

    private function availability(ExamType $examType, ExamAttemptType $type, ?string $locale): ExamPoolAvailability
    {
        $locale = $this->normalizeContentLocale($locale);
        $requiredStandard = $examType->standardQuestionCount();
        $requiredIllustrated = $examType->illustratedGroupCount();
        $availableStandard = $this->pool($examType, QuestionType::StandardTrueFalse, $type, $locale)->count();
        $availableIllustrated = $this->pool($examType, QuestionType::IllustratedGroup, $type, $locale)->count();

        $selectedStandard = min($availableStandard, $requiredStandard);
        $selectedIllustrated = min($availableIllustrated, $requiredIllustrated);
        $maxScore = ($selectedStandard * $examType->pointsPerStandardQuestion())
            + ($selectedIllustrated * $examType->pointsPerIllustratedGroup());

        if ($type === ExamAttemptType::Full) {
            $canStart = $availableStandard >= $requiredStandard && $availableIllustrated >= $requiredIllustrated;

            $message = null;

            if (! $canStart) {
                $baseShortage = $this->fullShortageMessage(
                    $requiredStandard,
                    $requiredIllustrated,
                    $availableStandard,
                    $availableIllustrated,
                );

                $message = $locale === 'ja'
                    ? $baseShortage
                    : __('exams.translation_pool_shortage').' '.$baseShortage;
            }

            return new ExamPoolAvailability(
                canStart: $canStart,
                availableStandard: $availableStandard,
                availableIllustrated: $availableIllustrated,
                requiredStandard: $requiredStandard,
                requiredIllustrated: $requiredIllustrated,
                questionCount: $canStart ? $requiredStandard + $requiredIllustrated : 0,
                maxScore: $canStart ? $maxScore : 0,
                message: $message,
            );
        }

        $questionCount = $selectedStandard + $selectedIllustrated;
        $canStart = $questionCount > 0;

        return new ExamPoolAvailability(
            canStart: $canStart,
            availableStandard: $selectedStandard,
            availableIllustrated: $selectedIllustrated,
            requiredStandard: $requiredStandard,
            requiredIllustrated: $requiredIllustrated,
            questionCount: $questionCount,
            maxScore: $maxScore,
            message: $canStart ? null : __('exams.no_sample_questions'),
        );
    }

    /**
     * @return Collection<int, Question>
     */
    private function draw(
        ExamType $examType,
        QuestionType $questionType,
        ExamAttemptType $attemptType,
        int $count,
        string $locale,
    ): Collection {
        if ($count < 1) {
            return collect();
        }

        return $this->pool($examType, $questionType, $attemptType, $locale)
            ->with([
                'category',
                'translations',
                'statements' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'statements.translations',
                'media' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            ])
            ->orderBy('id')
            ->get()
            ->shuffle()
            ->take($count)
            ->values();
    }

    /**
     * @return Builder<Question>
     */
    private function pool(
        ExamType $examType,
        QuestionType $questionType,
        ExamAttemptType $attemptType,
        string $locale,
    ): Builder {
        $query = Question::query()
            ->where('type', $questionType)
            ->whereHas('examTypes', fn (Builder $examTypes) => $examTypes->whereKey($examType->id));

        if ($attemptType === ExamAttemptType::Demo) {
            $query->where('is_demo', true)->where('status', QuestionStatus::Demo);
        } else {
            $query
                ->where('is_demo', false)
                ->where('status', QuestionStatus::Published)
                ->where('content_review_status', ContentReviewStatus::Reviewed);
        }

        if ($questionType === QuestionType::IllustratedGroup) {
            $query->has('statements', '=', $examType->statementsPerIllustratedGroup());
        }

        // Full-length public mocks require a complete translated pool for non-Japanese locales.
        // Demo sessions may start with partial coverage and show an unavailable notice per question.
        if ($attemptType === ExamAttemptType::Full && $locale !== 'ja') {
            $query->whereHas(
                'translations',
                fn (Builder $translations) => $translations
                    ->where('locale', $locale)
                    ->whereNotNull('question_text')
                    ->where('question_text', '!=', ''),
            );

            if ($questionType === QuestionType::IllustratedGroup) {
                $requiredStatements = $examType->statementsPerIllustratedGroup();

                $query->whereDoesntHave(
                    'statements',
                    fn (Builder $statements) => $statements->whereDoesntHave(
                        'translations',
                        fn (Builder $translations) => $translations
                            ->where('locale', $locale)
                            ->whereNotNull('question_text')
                            ->where('question_text', '!=', ''),
                    ),
                )->has('statements', '=', $requiredStatements);
            }
        }

        return $query;
    }

    private function fullShortageMessage(
        int $requiredStandard,
        int $requiredIllustrated,
        int $availableStandard,
        int $availableIllustrated,
    ): string {
        $needed = $requiredStandard.' '.__('exams.questions');

        if ($requiredIllustrated > 0) {
            $needed .= ' + '.$requiredIllustrated;
        }

        $found = $availableStandard;

        if ($requiredIllustrated > 0) {
            $found .= ' + '.$availableIllustrated;
        }

        return __('exams.full_shortage', [
            'needed' => $needed,
            'found' => (string) $found,
        ]);
    }

    private function normalizeContentLocale(?string $locale): string
    {
        if ($locale === null || $locale === '') {
            $locale = (string) session('locale', $this->locales->defaultLocale());
        }

        return $this->locales->normalize($locale) ?? $this->locales->defaultLocale();
    }
}
