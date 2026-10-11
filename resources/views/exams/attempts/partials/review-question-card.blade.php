@props([
    'question',
    'tone' => 'incorrect',
])

<article @class([
    'rounded-3xl border p-4 shadow-soft sm:p-5',
    'border-vermilion/35 bg-vermilion-soft/40' => $tone === 'incorrect',
    'border-border bg-paper' => $tone === 'unanswered',
    'border-matcha/30 bg-matcha/5' => $tone === 'correct',
])>
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h3 class="text-base font-bold text-navy">{{ __('exams.question_n', ['n' => $question['position']]) }}</h3>
        <span @class([
            'inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold',
            'bg-vermilion-soft text-vermilion' => $tone === 'incorrect',
            'bg-surface text-navy-muted' => $tone === 'unanswered',
            'bg-matcha/15 text-matcha' => $tone === 'correct',
        ])>
            <span aria-hidden="true">{{ $tone === 'incorrect' ? '×' : ($tone === 'unanswered' ? '–' : '○') }}</span>
            {{ $tone === 'incorrect' ? __('results.incorrect') : ($tone === 'unanswered' ? __('results.unanswered') : __('results.correct')) }}
            · {{ $question['points_awarded'] }} / {{ $question['scoring_weight'] }}
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
    @endunless

    <div class="mt-4 text-lg leading-relaxed text-navy" lang="{{ $question['stem_lang'] }}">{!! $question['stem_html'] !!}</div>

    @if ($question['illustrated'])
        <p class="mt-4 text-sm text-navy-muted">{{ __('results.illustrated_scoring_note') }}</p>
        <div class="mt-4 space-y-4">
            @foreach ($question['statements'] as $statement)
                <section class="rounded-2xl bg-paper/80 p-4">
                    <h4 class="text-sm font-semibold text-navy">{{ __('exams.statement_n', ['n' => $loop->iteration]) }}</h4>
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
                    @if ($statement['explanation_html'] ?? null)
                        <p class="mt-3 text-sm text-navy-muted" lang="{{ $statement['explanation_lang'] }}">
                            <span class="font-semibold text-navy">{{ __('results.explanation') }}. </span>
                            {!! $statement['explanation_html'] !!}
                        </p>
                    @endif
                    @if ($statement['nuance_note'] ?? null)
                        <p class="mt-2 text-sm text-navy-muted">
                            <span class="font-semibold text-navy">{{ __('results.translation_note') }} </span>{{ $statement['nuance_note'] }}
                        </p>
                    @endif
                </section>
            @endforeach
        </div>
    @else
        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
            <div class="rounded-2xl bg-paper/80 p-4">
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
            <div class="rounded-2xl bg-paper/80 p-4">
                <dt class="font-semibold text-navy">{{ __('results.correct_answer') }}</dt>
                <dd class="mt-1">{{ $question['correct_answer'] ? __('results.true_marked') : __('results.false_marked') }}</dd>
            </div>
        </dl>

        @if ($question['explanation_html'] ?? null)
            <p class="mt-4 text-sm leading-relaxed text-navy-muted" lang="{{ $question['explanation_lang'] }}">
                <span class="font-semibold text-navy">{{ __('results.explanation') }}. </span>
                {!! $question['explanation_html'] !!}
            </p>
        @endif
        @if ($question['nuance_note'] ?? null)
            <p class="mt-2 text-sm text-navy-muted">
                <span class="font-semibold text-navy">{{ __('results.translation_note') }} </span>{{ $question['nuance_note'] }}
            </p>
        @endif
    @endif
</article>
