<div
    wire:poll.30s="closeIfExpired"
    class="exam-runner space-y-5 sm:space-y-6"
    data-exam-runner
    data-one-way="true"
    x-data="{ submitting: false }"
>
    @if ($attempt->isAdminPreview())
        <div class="rounded-2xl border-2 border-amber-500 bg-amber-50 px-4 py-3 text-sm text-amber-950" role="note" data-admin-preview-banner>
            <p class="font-bold">管理者用・未承認問題の試験プレビュー</p>
            <p class="mt-1">{{ __('exams.admin_preview_banner_body') }}</p>
        </div>
    @elseif ($attempt->isDemo())
        <div class="rounded-2xl border border-amber/40 bg-amber-soft px-4 py-3 text-sm text-navy" role="note">
            <p class="font-semibold">{{ __('exams.demo_banner_title') }}</p>
            <p class="mt-1">
                {{ __('exams.demo_banner_body_count', [
                    'count' => $attempt->question_count,
                    'total' => $attempt->examType->standardQuestionCount() + $attempt->examType->illustratedGroupCount(),
                ]) }}
            </p>
        </div>
    @endif

    <header class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(14rem,18rem)] lg:items-stretch">
        <div class="exam-status-bar flex flex-col justify-between rounded-3xl border border-border bg-gradient-to-br from-washi via-paper to-surface p-4 shadow-soft sm:p-5">
            <div class="flex min-w-0 items-start gap-3">
                <x-logo class="mt-0.5 h-9 w-auto max-w-[10rem] shrink-0 sm:h-10" />
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-navy-muted">
                        @if ($attempt->isAdminPreview())
                            管理者用・未承認問題の試験プレビュー
                        @elseif ($attempt->isDemo())
                            {{ __('exams.demo_banner_title') }}
                        @else
                            {{ __('exams.practice_simulation') }}
                        @endif
                    </p>
                    <h1 class="truncate text-lg font-bold text-navy sm:text-xl">
                        {{ $attempt->examType->name_ja }}
                        <span class="font-medium text-navy-muted">· {{ $attempt->examType->name }}</span>
                    </h1>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <p class="exam-status-chip exam-status-chip--neutral">
                    <span class="exam-status-chip__label">{{ __('exams.questions') }}</span>
                    <span class="exam-status-chip__value">
                        {{ __('exams.question_of', ['current' => min($attempt->current_position, $attempt->question_count), 'total' => $attempt->question_count]) }}
                    </span>
                </p>
                <p class="exam-status-chip exam-status-chip--answered">
                    <span class="exam-status-chip__label">{{ __('exams.answered') }}</span>
                    <span class="exam-status-chip__value">{{ __('exams.answered_count', ['answered' => $answeredCount, 'total' => $attempt->question_count]) }}</span>
                </p>
                <a
                    href="{{ $exitUrl }}"
                    class="inline-flex min-h-11 items-center justify-center rounded-2xl border border-border bg-paper px-3.5 py-2 text-sm font-semibold text-navy-muted transition duration-300 ease-menkyo hover:border-accent/35 hover:text-navy focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                >
                    {{ __('exams.exit_exam') }}
                </a>
            </div>

            @php
                $progressPercent = $attempt->question_count > 0
                    ? (int) round(($answeredCount / $attempt->question_count) * 100)
                    : 0;
            @endphp
            <div
                class="exam-progress-bar mt-4"
                role="progressbar"
                aria-valuemin="0"
                aria-valuemax="{{ $attempt->question_count }}"
                aria-valuenow="{{ $answeredCount }}"
                aria-label="{{ __('exams.answered_count', ['answered' => $answeredCount, 'total' => $attempt->question_count]) }}"
            >
                <span style="width: {{ $progressPercent }}%"></span>
            </div>
        </div>

        <aside
            class="exam-timer-card relative overflow-hidden rounded-3xl border border-navy/20 bg-gradient-to-br from-navy via-accent to-navy p-5 text-white shadow-lift sm:p-6"
            data-exam-timer-card
            wire:ignore
            x-data="{
                remaining: 0,
                fired: false,
                total: {{ (int) $totalSeconds }},
                init() {
                    const offset = {{ (int) $serverNowMs }} - Date.now();
                    const tick = () => {
                        this.remaining = Math.max(0, {{ (int) $expiresAtMs }} - (Date.now() + offset));
                        if (this.remaining === 0 && ! this.fired) {
                            this.fired = true;
                            $wire.submitExam();
                        }
                    };
                    tick();
                    setInterval(tick, 1000);
                    document.addEventListener('visibilitychange', () => tick());
                },
                get label() {
                    const total = Math.ceil(this.remaining / 1000);
                    const minutes = Math.floor(total / 60);
                    const seconds = total % 60;
                    return String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
                },
                get ratio() {
                    return Math.max(0, Math.min(1, this.remaining / (this.total * 1000)));
                },
                get urgent() {
                    return this.remaining > 0 && this.remaining <= 60000;
                },
                get warning() {
                    return this.remaining > 60000 && this.remaining <= 300000;
                }
            }"
            x-init="init()"
            :class="urgent ? 'exam-timer-card--urgent' : (warning ? 'exam-timer-card--warning' : '')"
            role="timer"
            aria-live="off"
            aria-atomic="true"
        >
            <div class="pointer-events-none absolute -right-10 -top-10 h-32 w-32 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/75">{{ __('exams.time_remaining') }}</p>
            <p class="mt-3 font-mono text-5xl font-bold tabular-nums tracking-tight sm:text-6xl" x-text="label">--:--</p>
            <div class="exam-timer-track mt-5" aria-hidden="true">
                <span class="exam-timer-track__fill" :style="'width:' + (ratio * 100) + '%'"></span>
            </div>
            <p class="sr-only">{{ __('exams.time_remaining') }}</p>
        </aside>
    </header>

    @if ($answerError)
        <p class="rounded-2xl border border-vermilion/35 bg-vermilion-soft px-4 py-3 text-sm font-medium text-navy" role="alert" data-exam-answer-error>
            {{ $answerError }}
        </p>
    @endif

    @if ($allAnswered)
        <section class="rounded-3xl border border-matcha/30 bg-matcha/10 p-5 shadow-soft sm:p-6" data-exam-complete-card>
            <h2 class="text-xl font-bold text-navy">{{ __('exams.exam_ready_title') }}</h2>
            <p class="mt-2 text-sm leading-relaxed text-navy-muted">{{ __('exams.exam_ready_body') }}</p>
            <button type="button" wire:click="openSubmitModal" class="btn-primary mt-5 w-full sm:w-auto">
                {{ __('exams.open_submit_confirmation') }}
            </button>
        </section>
    @elseif ($current)
        <article
            wire:key="exam-question-{{ $current['id'] }}-{{ $attempt->current_position }}"
            class="exam-question-card is-changing space-y-5"
            aria-labelledby="exam-question-title"
            data-exam-question-card
        >
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 id="exam-question-title" class="text-base font-semibold text-navy sm:text-lg">{{ __('exams.question_n', ['n' => $current['position']]) }}</h2>
                    @if ($current['category'])
                        <span class="rounded-full bg-accent/10 px-3 py-1 text-xs font-semibold text-accent">{{ $current['category'] }}</span>
                    @endif
                    @if ($current['candidate_id'] ?? null)
                        <span class="rounded-full bg-navy/5 px-3 py-1 font-mono text-xs font-semibold text-navy">{{ $current['candidate_id'] }}</span>
                    @endif
                    @if ($current['needs_clarification'] ?? false)
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-900" data-preview-clarification-warning>{{ __('exams.admin_preview_clarification_warning') }}</span>
                    @endif
                </div>
                <p class="text-xs font-semibold text-navy-muted">{{ __('exams.one_way_hint') }}</p>
            </div>

            @if ($current['media'])
                <img
                    src="{{ $current['media']['url'] }}"
                    alt="{{ $current['media']['alt'] }}"
                    class="mx-auto max-h-64 w-full rounded-2xl border border-border bg-surface object-contain"
                >
            @endif

            @unless ($current['available'])
                <div class="rounded-2xl border border-amber/40 bg-amber-soft px-4 py-3 text-sm text-navy" role="status">
                    <p class="font-semibold">{{ __('exams.translation_unavailable') }}</p>
                    <p class="mt-1">{{ __('exams.translation_unavailable_detail') }}</p>
                </div>
                @if ($current['shows_source_japanese'])
                    <p class="text-xs font-semibold uppercase tracking-wide text-navy-muted">{{ __('exams.source_japanese_label') }}</p>
                @endif
            @endunless

            <div class="text-lg leading-relaxed text-navy sm:text-xl" lang="{{ $current['stem_lang'] }}">
                {!! $current['stem_html'] !!}
            </div>

            @if ($current['illustrated'])
                <div class="space-y-4">
                    @foreach ($current['statements'] as $statement)
                        <section wire:key="statement-{{ $current['id'] }}-{{ $statement['slot'] }}" class="rounded-2xl border border-border bg-surface/40 p-4">
                            <h3 class="text-sm font-semibold text-navy">{{ __('exams.statement_n', ['n' => $loop->iteration]) }}</h3>
                            @unless ($statement['available'])
                                <p class="mt-2 text-xs font-semibold text-navy">{{ __('exams.translation_unavailable') }}</p>
                            @endunless
                            <div class="mt-2 leading-relaxed text-navy" lang="{{ $statement['stem_lang'] }}">{!! $statement['stem_html'] !!}</div>
                            <div class="mt-4">
                                <x-exams.choice
                                    :group="'answer-'.$current['id'].'-'.$statement['slot']"
                                    :question-id="$current['id']"
                                    :answer-slot="$statement['slot']"
                                    :selected="$statement['selected']"
                                    :locked="$statement['selected'] !== null"
                                    :label="__('exams.statement_n', ['n' => $loop->iteration])"
                                />
                            </div>
                        </section>
                    @endforeach
                    <p class="text-sm text-navy-muted">{{ __('exams.illustrated_group_hint') }}</p>

                    @if ($groupReady)
                        <button
                            type="button"
                            wire:click="confirmIllustratedGroup({{ $current['id'] }})"
                            wire:loading.attr="disabled"
                            wire:target="confirmIllustratedGroup"
                            class="btn-primary w-full sm:w-auto"
                            data-confirm-group
                        >
                            <span wire:loading.remove wire:target="confirmIllustratedGroup">{{ __('exams.confirm_group_answers') }}</span>
                            <span wire:loading wire:target="confirmIllustratedGroup">{{ __('exams.saving') }}</span>
                        </button>
                    @endif
                </div>
            @else
                <div wire:loading.class="opacity-70 pointer-events-none" wire:target="selectAnswer">
                    <x-exams.choice
                        :group="'answer-'.$current['id']"
                        :question-id="$current['id']"
                        :answer-slot="0"
                        :selected="$current['selected']"
                        :locked="$current['selected'] !== null"
                        :label="__('exams.true_or_false')"
                    />
                </div>
            @endif
        </article>
    @endif

    <section class="rounded-3xl border border-border bg-paper p-4 sm:p-5" aria-label="{{ __('exams.progress_tracker') }}" data-exam-palette>
        <div class="flex flex-wrap items-end justify-between gap-2">
            <h2 class="text-sm font-semibold text-navy">{{ __('exams.progress_tracker') }}</h2>
            <ul class="flex flex-wrap gap-3 text-xs text-navy-muted">
                <li class="inline-flex items-center gap-1.5"><span class="exam-palette-legend exam-palette-legend--current"></span>{{ __('exams.palette_current') }}</li>
                <li class="inline-flex items-center gap-1.5"><span class="exam-palette-legend exam-palette-legend--answered"></span>{{ __('exams.palette_answered') }}</li>
                <li class="inline-flex items-center gap-1.5"><span class="exam-palette-legend exam-palette-legend--unanswered"></span>{{ __('exams.palette_unanswered') }}</li>
            </ul>
        </div>
        <div class="mt-3 grid grid-cols-5 gap-2 sm:grid-cols-8 md:grid-cols-10" role="list">
            @foreach ($palette as $item)
                <div
                    role="listitem"
                    wire:key="palette-{{ $item['position'] }}"
                    @class([
                        'exam-palette-btn exam-palette-btn--readonly',
                        'exam-palette-btn--current' => $item['current'],
                        'exam-palette-btn--answered' => ! $item['current'] && $item['answered'],
                        'exam-palette-btn--unanswered' => ! $item['current'] && ! $item['answered'],
                    ])
                    aria-label="{{ __('exams.question_n', ['n' => $item['position']]) }} — {{ $item['current'] ? __('exams.palette_current') : ($item['answered'] ? __('exams.palette_answered') : __('exams.palette_unanswered')) }}"
                >
                    <span aria-hidden="true">{{ $item['position'] }}</span>
                </div>
            @endforeach
        </div>
        <p class="mt-3 text-xs text-navy-muted">{{ __('exams.palette_readonly_hint') }}</p>
    </section>

    @unless ($allAnswered)
        <div class="flex justify-end">
            <button type="button" wire:click="openSubmitModal" class="btn-secondary text-sm">
                {{ __('exams.submit_early') }}
            </button>
        </div>
    @endunless

    <p class="text-xs text-navy-muted" wire:loading wire:target="selectAnswer, confirmIllustratedGroup">{{ __('exams.saving') }}</p>

    <div
        x-show="$wire.showSubmitModal"
        x-cloak
        class="fixed inset-0 z-50 flex items-end justify-center overflow-y-auto px-4 py-6 sm:items-center sm:px-0"
        role="dialog"
        aria-modal="true"
        aria-labelledby="exam-submit-title"
        data-exam-submit-modal
        @keydown.escape.window="if (! submitting) $wire.closeSubmitModal()"
    >
        <div
            class="fixed inset-0 bg-navy/55 backdrop-blur-[2px] transition-opacity"
            x-transition.opacity
            @click="if (! submitting) $wire.closeSubmitModal()"
        ></div>

        <div
            class="relative z-10 w-full max-w-lg overflow-hidden rounded-3xl border border-border bg-washi p-6 shadow-lift sm:p-8"
            x-transition
            @click.stop
        >
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-accent">{{ $attempt->examType->name_ja }}</p>
            <h2 id="exam-submit-title" class="mt-2 text-2xl font-bold text-navy">{{ __('exams.submit_modal_title') }}</h2>
            <p class="mt-3 text-sm leading-relaxed text-navy-muted">{{ __('exams.submit_modal_body') }}</p>

            <dl class="mt-5 grid grid-cols-3 gap-3 text-center text-sm">
                <div class="rounded-2xl border border-border bg-paper px-3 py-3">
                    <dt class="text-xs text-navy-muted">{{ __('exams.answered') }}</dt>
                    <dd class="mt-1 text-lg font-bold text-navy">{{ $answeredCount }}</dd>
                </div>
                <div class="rounded-2xl border border-border bg-paper px-3 py-3">
                    <dt class="text-xs text-navy-muted">{{ __('results.unanswered') }}</dt>
                    <dd class="mt-1 text-lg font-bold text-navy">{{ $unansweredCount }}</dd>
                </div>
                <div class="rounded-2xl border border-border bg-paper px-3 py-3">
                    <dt class="text-xs text-navy-muted">{{ __('exams.questions') }}</dt>
                    <dd class="mt-1 text-lg font-bold text-navy">{{ $attempt->question_count }}</dd>
                </div>
            </dl>

            @if ($unansweredCount > 0)
                <p class="mt-4 rounded-2xl border border-amber/40 bg-amber-soft px-4 py-3 text-sm text-navy" role="status">
                    {{ __('exams.submit_unanswered_warning', ['count' => $unansweredCount]) }}
                </p>
            @endif

            <p class="mt-4 text-sm font-medium text-navy">{{ __('exams.submit_lock_warning') }}</p>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    class="btn-secondary w-full sm:w-auto"
                    wire:click="closeSubmitModal"
                    x-bind:disabled="submitting"
                >
                    {{ __('exams.cancel') }}
                </button>
                <button
                    type="button"
                    class="btn-primary w-full sm:w-auto"
                    wire:click="submitExam"
                    wire:loading.attr="disabled"
                    wire:target="submitExam"
                    x-on:click="submitting = true"
                    data-confirm-submit
                >
                    <span wire:loading.remove wire:target="submitExam">{{ __('exams.submit_and_view_results') }}</span>
                    <span wire:loading wire:target="submitExam">{{ __('exams.submitting') }}</span>
                </button>
            </div>
        </div>
    </div>
</div>
