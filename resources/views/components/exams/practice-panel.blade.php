@props(['offer'])

@php
    $illustratedPart = ($offer['illustrated_count'] ?? 0) > 0
        ? __('exams.illustrated_part', ['count' => $offer['illustrated_count']])
        : '';
    $demoCount = (int) ($offer['demo']['question_count'] ?? 0);
    $fullReady = (bool) ($offer['full']['can_start'] ?? false);
    $hasResumeFull = filled($offer['resume']['full'] ?? null);
    $hasResumeDemo = filled($offer['resume']['demo'] ?? null);
@endphp

<section
    {{ $attributes->class('exam-practice-panel relative overflow-hidden rounded-[1.75rem] border border-accent/35 p-6 sm:p-8 lg:p-10') }}
    data-practice-panel="featured"
    aria-labelledby="practice-panel-heading"
>
    <div class="exam-practice-panel__accent" aria-hidden="true"></div>
    <div class="exam-practice-panel__glow pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full bg-accent/20 blur-3xl" aria-hidden="true"></div>
    <div class="exam-practice-panel__glow pointer-events-none absolute -bottom-24 -left-10 h-48 w-48 rounded-full bg-gold/20 blur-3xl" aria-hidden="true"></div>
    <div class="exam-practice-panel__glow pointer-events-none absolute right-1/3 top-1/2 h-32 w-32 rounded-full bg-matcha/15 blur-3xl" aria-hidden="true"></div>

    <div class="relative">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-accent">{{ __('exams.practice_kicker') }}</p>
        <h2 id="practice-panel-heading" class="mt-2 text-2xl font-bold tracking-tight text-navy sm:text-3xl lg:text-[2.05rem]">
            {{ __('exams.practice_exam') }}
        </h2>

        @unless ($offer['configured'] ?? false)
            <p class="mt-3 max-w-2xl text-sm leading-relaxed text-navy-muted sm:text-base">{{ $offer['message'] ?? __('exams.practice_not_configured') }}</p>
        @else
            <p class="mt-3 max-w-3xl text-sm leading-relaxed text-navy-muted sm:text-base">
                {{ __('exams.practice_offer_summary', [
                    'name' => $offer['name'],
                    'name_ja' => $offer['name_ja'],
                    'standard' => $offer['standard_count'],
                    'illustrated_part' => $illustratedPart,
                    'minutes' => $offer['duration_minutes'],
                    'passing' => $offer['passing_score'],
                    'max' => $offer['max_score'],
                ]) }}
            </p>

            <ul class="mt-5 flex flex-wrap gap-2" aria-label="{{ __('exams.practice_facts_label') }}">
                <li class="exam-fact-chip exam-fact-chip--questions">
                    <span class="exam-fact-chip__value">{{ $offer['standard_count'] + $offer['illustrated_count'] }}</span>
                    <span class="exam-fact-chip__label">{{ __('exams.fact_questions') }}</span>
                </li>
                <li class="exam-fact-chip">
                    <span class="exam-fact-chip__value">{{ $offer['duration_minutes'] }}</span>
                    <span class="exam-fact-chip__label">{{ __('exams.fact_minutes') }}</span>
                </li>
                <li class="exam-fact-chip exam-fact-chip--pass">
                    <span class="exam-fact-chip__value">{{ $offer['passing_score'] }}/{{ $offer['max_score'] }}</span>
                    <span class="exam-fact-chip__label">{{ __('exams.fact_pass') }}</span>
                </li>
                @if (($offer['demo_enabled'] ?? false) && $demoCount > 0)
                    <li class="exam-fact-chip exam-fact-chip--demo">
                        <span class="exam-fact-chip__value">{{ $demoCount }}</span>
                        <span class="exam-fact-chip__label">{{ __('exams.fact_demo_samples') }}</span>
                    </li>
                @endif
            </ul>

            @if (session('exam_error'))
                <p class="mt-4 rounded-2xl border border-amber/40 bg-amber-soft px-4 py-3 text-sm text-navy" role="alert">{{ session('exam_error') }}</p>
            @endif

            <div class="mt-6 grid gap-4 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)] lg:items-stretch">
                <div class="rounded-3xl border border-accent/25 bg-gradient-to-br from-white via-white to-accent/5 p-5 shadow-soft backdrop-blur-sm sm:p-6">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-base font-semibold text-navy">{{ __('exams.full_practice_heading') }}</h3>
                        @if ($hasResumeFull || $fullReady)
                            <span class="inline-flex items-center rounded-full bg-matcha/20 px-2.5 py-1 text-xs font-semibold text-matcha">{{ __('exams.full_status_ready') }}</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-navy/8 px-2.5 py-1 text-xs font-semibold text-navy-muted">{{ __('exams.full_status_unavailable') }}</span>
                        @endif
                    </div>

                    @auth
                        @if ($hasResumeFull)
                            <p class="mt-2 text-sm text-navy-muted">{{ __('exams.resume_full_hint') }}</p>
                            <a href="{{ route('exams.attempts.show', $offer['resume']['full']) }}" class="btn-primary mt-4 w-full">{{ __('exams.continue_full_practice') }}</a>
                        @elseif ($fullReady)
                            <p class="mt-2 text-sm text-navy-muted">{{ __('exams.full_ready_hint') }}</p>
                            <form method="POST" action="{{ route('exams.attempts.store', $offer['slug']) }}" class="mt-4">
                                @csrf
                                <input type="hidden" name="attempt_type" value="full">
                                <button type="submit" class="btn-primary w-full">
                                    {{ __('exams.start_full_practice_count', ['count' => $offer['full']['question_count']]) }}
                                </button>
                            </form>
                        @else
                            <p class="mt-2 text-sm leading-relaxed text-navy-muted" data-full-practice-message>{{ $offer['full']['message'] }}</p>
                            <button
                                type="button"
                                disabled
                                class="mt-4 flex w-full min-h-12 cursor-not-allowed items-center justify-center rounded-2xl border border-dashed border-border bg-surface/80 px-4 py-3 text-sm font-semibold text-navy-muted opacity-70"
                                data-full-practice-unavailable
                                aria-disabled="true"
                            >
                                {{ __('exams.full_practice_unavailable') }}
                            </button>
                        @endif
                    @else
                        <p class="mt-2 text-sm text-navy-muted">{{ __('exams.practice_history_saved') }}</p>
                        <a href="{{ route('login') }}" class="btn-primary mt-4 w-full">{{ __('exams.sign_in_to_practice') }}</a>
                    @endauth
                </div>

                <div class="rounded-3xl border border-gold/35 bg-gradient-to-br from-gold/15 via-white/95 to-matcha/10 p-5 shadow-soft sm:p-6">
                    <h3 class="text-base font-semibold text-navy">{{ __('exams.demo_practice_heading') }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-navy-muted">{{ __('exams.demo_practice_lead') }}</p>

                    @auth
                        @if ($offer['demo_enabled'])
                            @if ($hasResumeDemo)
                                <a href="{{ route('exams.attempts.show', $offer['resume']['demo']) }}" class="btn-gold mt-5 w-full" data-demo-practice-cta>
                                    {{ __('exams.continue_demo_practice') }}
                                </a>
                            @elseif ($offer['demo']['can_start'])
                                <form method="POST" action="{{ route('exams.attempts.store', $offer['slug']) }}" class="mt-5">
                                    @csrf
                                    <input type="hidden" name="attempt_type" value="demo">
                                    <button type="submit" class="btn-gold w-full" data-demo-practice-cta>
                                        {{ __('exams.start_demo_practice_count', ['count' => $offer['demo']['question_count']]) }}
                                    </button>
                                </form>
                            @else
                                <p class="mt-4 text-sm text-navy-muted">{{ $offer['demo']['message'] }}</p>
                            @endif
                        @else
                            <p class="mt-4 text-sm text-navy-muted">{{ __('exams.demo_not_available') }}</p>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="btn-gold mt-5 w-full" data-demo-practice-cta>{{ __('exams.sign_in_to_practice') }}</a>
                    @endauth

                    <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                        <a href="{{ route('learn.traffic-rules') }}" class="btn-secondary w-full text-sm sm:flex-1">{{ __('exams.secondary_cta_rules') }}</a>
                        <a href="{{ route('learn.road-signs') }}" class="btn-vermilion w-full text-sm sm:flex-1">{{ __('exams.secondary_cta_signs') }}</a>
                        <a href="{{ route('learn.index') }}" class="btn-secondary w-full text-sm sm:flex-1">{{ __('exams.secondary_cta_learn') }}</a>
                        <a href="{{ route('learn.study-methods') }}" class="btn-secondary w-full text-sm sm:flex-1">{{ __('exams.secondary_cta_study') }}</a>
                    </div>

                    @if ($offer['demo_enabled'] ?? false)
                        <p class="mt-4 text-xs leading-relaxed text-navy-muted" data-demo-disclaimer>
                            {{ __('exams.demo_banner_title') }} — {{ __('exams.demo_banner_body') }}
                        </p>
                    @endif
                </div>
            </div>
        @endunless
    </div>
</section>
