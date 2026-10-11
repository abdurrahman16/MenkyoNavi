<x-layouts.exam
    :title="__('results.answer_review').' · '.$attempt->examType->name.' | MenkyoNavi'"
    :description="__('results.answer_review')"
    robots="noindex, nofollow"
>
    <div class="space-y-6">
        <header class="space-y-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-navy-muted">{{ $attempt->examType->name_ja }}</p>
            <h1 class="text-2xl font-bold text-navy">{{ __('results.answer_review') }}</h1>
            <p class="text-sm text-navy-muted">
                {{ $attempt->examType->name }} · {{ $attempt->score }} / {{ $attempt->max_score }}
                @if ($attempt->isDemo())
                    · {{ __('exams.demo_banner_title') }}
                @endif
            </p>
        </header>

        @if ($attempt->isDemo())
            <p class="rounded-2xl border border-amber/40 bg-amber-soft px-4 py-3 text-sm text-navy" role="note">
                {{ __('results.demo_review_note') }}
            </p>
        @endif

        <div class="space-y-5">
            @foreach ($questions as $question)
                <article class="rounded-2xl border border-border p-4 sm:p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-base font-bold text-navy">{{ __('exams.question_n', ['n' => $question['position']]) }}</h2>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $question['is_correct'] ? 'bg-teal/10 text-teal' : 'bg-surface text-navy' }}">
                            {{ $question['is_correct'] ? __('results.correct') : __('results.not_correct') }} · {{ $question['points_awarded'] }} / {{ $question['scoring_weight'] }}
                        </span>
                    </div>
                    @if ($question['category'])
                        <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-accent">{{ $question['category'] }}</p>
                    @endif

                    @if ($question['media'])
                        <img src="{{ $question['media']['url'] }}" alt="{{ $question['media']['alt'] }}" class="mt-4 max-h-64 w-full rounded-2xl border border-border object-contain">
                    @endif

                    @unless ($question['available'])
                        <div class="mt-4 rounded-2xl border border-amber/40 bg-amber-soft px-4 py-3 text-sm text-navy" role="status">
                            <p class="font-semibold">{{ __('exams.translation_unavailable') }}</p>
                            <p class="mt-1">{{ __('exams.translation_unavailable_detail') }}</p>
                        </div>
                        @if ($question['shows_source_japanese'])
                            <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-navy-muted">{{ __('exams.source_japanese_label') }}</p>
                        @endif
                    @endunless

                    <div class="mt-4 text-lg leading-relaxed text-navy" lang="{{ $question['stem_lang'] }}">{!! $question['stem_html'] !!}</div>

                    @if ($question['illustrated'])
                        <p class="mt-4 text-sm text-navy-muted">{{ __('results.illustrated_scoring_note') }}</p>
                        <div class="mt-4 space-y-4">
                            @foreach ($question['statements'] as $statement)
                                <section class="rounded-2xl bg-surface p-4">
                                    <h3 class="text-sm font-semibold text-navy">{{ __('exams.statement_n', ['n' => $loop->iteration]) }}</h3>
                                    @unless ($statement['available'])
                                        <p class="mt-2 text-xs font-semibold text-navy">{{ __('exams.translation_unavailable') }}</p>
                                    @endunless
                                    <div class="mt-2 text-navy" lang="{{ $statement['stem_lang'] }}">{!! $statement['stem_html'] !!}</div>
                                    <dl class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                                        <div>
                                            <dt class="font-semibold text-navy">{{ __('results.your_answer') }}</dt>
                                            <dd>
                                                @if ($statement['selected'] === null)
                                                    {{ __('results.unanswered') }}
                                                @elseif ($statement['selected'])
                                                    {{ __('results.true_marked') }}
                                                @else
                                                    {{ __('results.false_marked') }}
                                                @endif
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="font-semibold text-navy">{{ __('results.correct_answer') }}</dt>
                                            <dd>{{ $statement['correct_answer'] ? __('results.true_marked') : __('results.false_marked') }}</dd>
                                        </div>
                                    </dl>
                                    @if ($statement['explanation_html'])
                                        <p class="mt-3 text-sm text-navy-muted" lang="{{ $statement['explanation_lang'] }}">
                                            <span class="font-semibold text-navy">{{ __('results.explanation') }}. </span>
                                            {!! $statement['explanation_html'] !!}
                                        </p>
                                    @endif
                                    @if ($statement['nuance_note'])
                                        <p class="mt-2 text-sm text-navy-muted">
                                            <span class="font-semibold text-navy">{{ __('results.translation_note') }} </span>{{ $statement['nuance_note'] }}
                                        </p>
                                    @endif
                                </section>
                            @endforeach
                        </div>
                    @else
                        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                            <div class="rounded-2xl bg-surface p-4">
                                <dt class="font-semibold text-navy">{{ __('results.your_answer') }}</dt>
                                <dd class="mt-1">
                                    @if ($question['selected'] === null)
                                        {{ __('results.unanswered') }}
                                    @elseif ($question['selected'])
                                        {{ __('results.true_marked') }}
                                    @else
                                        {{ __('results.false_marked') }}
                                    @endif
                                </dd>
                            </div>
                            <div class="rounded-2xl bg-surface p-4">
                                <dt class="font-semibold text-navy">{{ __('results.correct_answer') }}</dt>
                                <dd class="mt-1">{{ $question['correct_answer'] ? __('results.true_marked') : __('results.false_marked') }}</dd>
                            </div>
                        </dl>
                        @if ($question['explanation_html'])
                            <p class="mt-4 text-sm leading-relaxed text-navy-muted" lang="{{ $question['explanation_lang'] }}">
                                <span class="font-semibold text-navy">{{ __('results.explanation') }}. </span>
                                {!! $question['explanation_html'] !!}
                            </p>
                        @endif
                        @if ($question['nuance_note'])
                            <p class="mt-2 text-sm leading-relaxed text-navy-muted">
                                <span class="font-semibold text-navy">{{ __('results.translation_note') }} </span>{{ $question['nuance_note'] }}
                            </p>
                        @endif
                    @endif
                </article>
            @endforeach
        </div>

        <a href="{{ route('exams.attempts.results', $attempt) }}" class="btn-secondary">{{ __('results.back_to_results') }}</a>
    </div>
</x-layouts.exam>
