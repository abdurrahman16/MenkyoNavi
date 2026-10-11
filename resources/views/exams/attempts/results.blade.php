<x-layouts.exam
    :title="__('results.result_title', ['name' => $attempt->examType->name]).' | MenkyoNavi'"
    :description="__('exams.practice_simulation')"
    robots="noindex, nofollow"
>
    <div class="space-y-8" data-exam-results>
        @if (session('exam_notice'))
            <p class="rounded-2xl border border-amber/40 bg-amber-soft px-4 py-3 text-sm font-medium text-navy" role="status">
                {{ session('exam_notice') }}
            </p>
        @endif

        @if ($attempt->isAdminPreview())
            <div class="rounded-2xl border-2 border-amber-500 bg-amber-50 px-4 py-4" role="note" data-admin-preview-banner>
                <p class="text-base font-bold text-amber-950">管理者用・未承認問題の試験プレビュー</p>
                <p class="mt-2 text-sm leading-relaxed text-amber-950">{{ __('exams.admin_preview_banner_body') }}</p>
                <p class="mt-2 text-sm font-semibold text-amber-950">{{ __('results.admin_preview_score_disclaimer') }}</p>
            </div>
        @elseif ($attempt->isDemo())
            <div class="rounded-2xl border border-amber/40 bg-amber-soft px-4 py-4" role="note">
                <p class="text-base font-bold text-navy">{{ __('exams.demo_banner_title') }}</p>
                <p class="mt-2 text-sm leading-relaxed text-navy">
                    {{ __('exams.demo_banner_body_count', [
                        'count' => $attempt->question_count,
                        'total' => $attempt->examType->standardQuestionCount() + $attempt->examType->illustratedGroupCount(),
                    ]) }}
                </p>
                <p class="mt-2 text-sm font-semibold text-navy">{{ __('results.demo_score_disclaimer') }}</p>
            </div>
        @endif

        <section class="overflow-hidden rounded-[1.75rem] border border-border bg-gradient-to-br from-washi via-paper to-surface p-6 shadow-lift sm:p-8" data-results-score-card>
            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-accent">
                        {{ $attempt->isDemo() ? __('exams.demo_banner_title') : __('exams.practice_simulation') }}
                    </p>
                    <h1 class="mt-2 text-2xl font-bold text-navy sm:text-3xl">{{ __('results.result_title', ['name' => $attempt->examType->name]) }}</h1>
                    <p class="mt-2 text-sm text-navy-muted">{{ $attempt->examType->name_ja }} · {{ optional($attempt->submitted_at)->timezone(config('app.timezone'))?->format('Y-m-d H:i') }}</p>
                    <p class="mt-4 inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold {{ $attempt->passed ? 'bg-matcha/15 text-matcha' : 'bg-vermilion-soft text-vermilion' }}">
                        {{ $attempt->passed ? __('results.benchmark_reached') : __('results.benchmark_not_reached') }}
                    </p>
                </div>

                <div class="motion-reveal-scale flex items-center gap-5" data-reveal>
                    <div
                        class="result-ring shrink-0"
                        data-result-ring="{{ $attempt->scorePercentage() }}"
                        aria-hidden="true"
                    >
                        <span>{{ $attempt->scorePercentage() }}%</span>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-navy-muted">{{ __('results.final_score') }}</p>
                        <p class="mt-1 text-4xl font-bold text-navy">{{ $attempt->score }} <span class="text-2xl text-navy-muted">/ {{ $attempt->max_score }}</span></p>
                        <p class="mt-2 text-sm text-navy-muted">{{ __('results.passing_score_for_session', ['passing' => $attempt->passing_score, 'max' => $attempt->max_score]) }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3" aria-label="{{ __('results.performance_stats') }}">
            <div class="rounded-3xl border border-matcha/30 bg-matcha/10 p-5 shadow-soft">
                <p class="text-xs font-semibold uppercase tracking-wide text-navy-muted">{{ __('results.correct') }}</p>
                <p class="mt-2 text-3xl font-bold text-navy">{{ $counts['correct'] }}</p>
            </div>
            <div class="rounded-3xl border border-vermilion/30 bg-vermilion-soft p-5 shadow-soft">
                <p class="text-xs font-semibold uppercase tracking-wide text-navy-muted">{{ __('results.incorrect') }}</p>
                <p class="mt-2 text-3xl font-bold text-navy">{{ $counts['incorrect'] }}</p>
            </div>
            <div class="rounded-3xl border border-border bg-paper p-5 shadow-soft">
                <p class="text-xs font-semibold uppercase tracking-wide text-navy-muted">{{ __('results.unanswered') }}</p>
                <p class="mt-2 text-3xl font-bold text-navy">{{ $counts['unanswered'] }}</p>
            </div>
            <div class="rounded-3xl border border-border bg-paper p-5 shadow-soft">
                <p class="text-xs font-semibold uppercase tracking-wide text-navy-muted">{{ __('exams.questions') }}</p>
                <p class="mt-2 text-3xl font-bold text-navy">{{ $attempt->question_count }}</p>
            </div>
            <div class="rounded-3xl border border-border bg-paper p-5 shadow-soft">
                <p class="text-xs font-semibold uppercase tracking-wide text-navy-muted">{{ __('results.time_taken') }}</p>
                <p class="mt-2 text-3xl font-bold text-navy">{{ $attempt->formattedDuration() }}</p>
            </div>
            <div class="rounded-3xl border border-border bg-paper p-5 shadow-soft">
                <p class="text-xs font-semibold uppercase tracking-wide text-navy-muted">{{ __('results.percentage') }}</p>
                <p class="mt-2 text-3xl font-bold text-navy">{{ $attempt->scorePercentage() }}%</p>
            </div>
        </section>

        @if ($categories !== [])
            <section aria-labelledby="category-breakdown-heading">
                <h2 id="category-breakdown-heading" class="text-lg font-bold text-navy">{{ __('results.category_breakdown') }}</h2>
                <div class="mt-3 overflow-x-auto rounded-3xl border border-border bg-paper p-4 shadow-soft">
                    <table class="min-w-full text-left text-sm">
                        <thead class="text-navy-muted">
                            <tr>
                                <th class="py-2 pr-4 font-semibold">{{ __('results.category') }}</th>
                                <th class="py-2 pr-4 font-semibold">{{ __('results.correct') }}</th>
                                <th class="py-2 pr-4 font-semibold">{{ __('results.incorrect') }}</th>
                                <th class="py-2 pr-4 font-semibold">{{ __('results.unanswered') }}</th>
                                <th class="py-2 font-semibold">{{ __('results.points') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($categories as $category)
                                <tr class="border-t border-border">
                                    <td class="py-3 pr-4 font-medium text-navy">{{ $category['category'] }}</td>
                                    <td class="py-3 pr-4">{{ $category['correct'] }}</td>
                                    <td class="py-3 pr-4">{{ $category['incorrect'] }}</td>
                                    <td class="py-3 pr-4">{{ $category['unanswered'] }}</td>
                                    <td class="py-3">{{ $category['points'] }} / {{ $category['max_points'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <section class="space-y-4" aria-label="{{ __('results.recommended_actions') }}" data-results-next-steps>
            <p class="text-sm leading-relaxed text-navy-muted">{{ __('results.next_steps_lead') }}</p>
            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                <a href="{{ route('exams.attempts.review', $attempt) }}" class="btn-primary">{{ __('results.review_all_answers') }}</a>
                @if (count($incorrectQuestions) > 0)
                    <a href="#incorrect-answers" class="btn-secondary">{{ __('results.review_mistakes') }}</a>
                @endif
                @unless ($attempt->isDemo() || $attempt->isAdminPreview())
                    <a href="{{ route('mistakes.index') }}" class="btn-secondary">{{ __('results.open_mistake_vault') }}</a>
                @endunless
                @unless ($attempt->isAdminPreview())
                    <a href="{{ route('learn.traffic-rules.pedestrian-priority') }}" class="btn-matcha">{{ __('results.study_pedestrian') }}</a>
                    <a href="{{ route('learn.traffic-rules.intersection-priority') }}" class="btn-secondary">{{ __('results.study_intersection') }}</a>
                    <a href="{{ route('learn.road-signs') }}" class="btn-vermilion">{{ __('exams.secondary_cta_signs') }}</a>
                    <a href="{{ route('learn.study-methods') }}" class="btn-secondary">{{ __('results.study_methods') }}</a>
                @endunless
                @php
                    $again = $attempt->isAdminPreview()
                        ? route('admin.questions.original-karimen-exam-preview')
                        : (\Illuminate\Support\Facades\Route::has('exams.'.$attempt->examType->slug)
                            ? route('exams.'.$attempt->examType->slug)
                            : route('exams.index'));
                @endphp
                <a href="{{ $again }}" class="btn-gold">{{ $attempt->isAdminPreview() ? __('admin.karimen_preview_restart') : __('results.start_another') }}</a>
                @unless ($attempt->isAdminPreview())
                    <a href="{{ route('exams.attempts.index') }}" class="btn-secondary">{{ __('exams.practice_history') }}</a>
                @endunless
            </div>
        </section>

        <section id="incorrect-answers" aria-labelledby="incorrect-heading" data-incorrect-review>
            <h2 id="incorrect-heading" class="text-xl font-bold text-navy">{{ __('results.incorrect_answers_heading') }}</h2>
            @if ($incorrectQuestions === [])
                <p class="mt-3 rounded-2xl border border-matcha/30 bg-matcha/10 px-4 py-3 text-sm text-navy">{{ __('results.no_incorrect_answers') }}</p>
            @else
                <div class="mt-4 space-y-4">
                    @foreach ($incorrectQuestions as $question)
                        @include('exams.attempts.partials.review-question-card', ['question' => $question, 'tone' => 'incorrect'])
                    @endforeach
                </div>
            @endif
        </section>

        <section id="unanswered-questions" aria-labelledby="unanswered-heading" data-unanswered-review>
            <h2 id="unanswered-heading" class="text-xl font-bold text-navy">{{ __('results.unanswered_heading') }}</h2>
            @if ($unansweredQuestions === [])
                <p class="mt-3 rounded-2xl border border-border bg-paper px-4 py-3 text-sm text-navy-muted">{{ __('results.no_unanswered_questions') }}</p>
            @else
                <div class="mt-4 space-y-4">
                    @foreach ($unansweredQuestions as $question)
                        @include('exams.attempts.partials.review-question-card', ['question' => $question, 'tone' => 'unanswered'])
                    @endforeach
                </div>
            @endif
        </section>

        <x-ui.disclaimer-notice />
    </div>
</x-layouts.exam>
