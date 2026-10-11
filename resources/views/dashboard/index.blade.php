<x-layouts.dashboard
    :title="__('panel.title').' | MenkyoNavi'"
>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if (request()->boolean('verified'))
            <div class="mb-6 rounded-2xl border border-matcha/30 bg-matcha/10 px-5 py-4 shadow-soft" role="status" data-email-verified-banner>
                <p class="text-base font-semibold text-navy">{{ __('panel.verified_banner_title') }}</p>
                <p class="mt-1 text-sm leading-relaxed text-navy/80">{{ __('panel.verified_banner_body') }}</p>
            </div>
        @endif

        <div class="rounded-2xl border border-border bg-white p-8 shadow-soft">
            <h1 class="text-2xl font-bold text-navy">
                {{ __('panel.welcome') }}{{ auth()->user()?->name ? ', ' . auth()->user()->name : '' }}
            </h1>
            <p class="mt-2 text-navy-muted">
                {{ __('panel.continue_studying') }}
            </p>
            <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('exams.karimen') }}" class="btn-primary w-full sm:w-auto" data-onboarding-demo-cta>
                    {{ __('panel.start_demo_practice') }}
                </a>
                <a href="{{ route('exams.index') }}" class="btn-gold w-full sm:w-auto">{{ __('panel.start_practice') }}</a>
                @if ($openAttempt)
                    <a href="{{ route('exams.attempts.show', $openAttempt) }}" class="btn-secondary w-full sm:w-auto">{{ __('panel.continue_attempt') }}</a>
                @endif
                <a href="{{ route('learn.index') }}" class="btn-secondary w-full sm:w-auto">{{ __('panel.browse_learn') }}</a>
            </div>
        </div>

        <section class="mt-10" aria-labelledby="exam-shortcuts-heading">
            <h2 id="exam-shortcuts-heading" class="text-lg font-semibold text-navy">{{ __('panel.exam_shortcuts') }}</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <a href="{{ route('exams.gaimen-kirikae') }}" class="rounded-2xl border border-border bg-white p-5 shadow-soft transition hover:border-accent/40">
                    <span class="text-sm font-medium text-accent">{{ config('menkyo.exam_labels.gaimen') }}</span>
                    <p class="mt-1 font-semibold text-navy">{{ __('home.gaimen_title') }}</p>
                </a>
                <a href="{{ route('exams.karimen') }}" class="rounded-2xl border border-border bg-white p-5 shadow-soft transition hover:border-accent/40">
                    <span class="text-sm font-medium text-accent">{{ config('menkyo.exam_labels.karimen') }}</span>
                    <p class="mt-1 font-semibold text-navy">{{ __('home.karimen_title') }}</p>
                </a>
                <a href="{{ route('exams.honmen') }}" class="rounded-2xl border border-border bg-white p-5 shadow-soft transition hover:border-accent/40">
                    <span class="text-sm font-medium text-accent">{{ config('menkyo.exam_labels.honmen') }}</span>
                    <p class="mt-1 font-semibold text-navy">{{ __('home.honmen_title') }}</p>
                </a>
            </div>
        </section>

        <div class="mt-10 grid gap-8 lg:grid-cols-2">
            <section aria-labelledby="progress-heading">
                <h2 id="progress-heading" class="text-lg font-semibold text-navy">{{ __('panel.study_progress') }}</h2>
                <div class="mt-4">
                    @if ($stats['completed_full'] === 0 && $stats['completed_demo'] === 0 && $stats['open'] === 0)
                        <x-ui.empty-state
                            :title="__('panel.no_progress_title')"
                            :description="__('panel.no_progress_body')"
                        />
                    @else
                        <dl class="grid gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl border border-border bg-white p-4 shadow-soft">
                                <dt class="text-xs font-medium text-navy-muted">{{ __('panel.completed_full') }}</dt>
                                <dd class="mt-1 text-2xl font-bold text-navy">{{ $stats['completed_full'] }}</dd>
                            </div>
                            <div class="rounded-2xl border border-border bg-white p-4 shadow-soft">
                                <dt class="text-xs font-medium text-navy-muted">{{ __('panel.completed_demo') }}</dt>
                                <dd class="mt-1 text-2xl font-bold text-navy">{{ $stats['completed_demo'] }}</dd>
                            </div>
                            <div class="rounded-2xl border border-border bg-white p-4 shadow-soft">
                                <dt class="text-xs font-medium text-navy-muted">{{ __('panel.open_attempts') }}</dt>
                                <dd class="mt-1 text-2xl font-bold text-navy">{{ $stats['open'] }}</dd>
                            </div>
                        </dl>
                    @endif
                    <a href="{{ route('learn.index') }}" class="mt-4 inline-block text-sm font-semibold text-accent hover:text-accent-dark">
                        {{ __('panel.browse_learn') }} →
                    </a>
                </div>
            </section>

            <section aria-labelledby="activity-heading">
                <h2 id="activity-heading" class="text-lg font-semibold text-navy">{{ __('panel.recent_activity') }}</h2>
                <div class="mt-4">
                    @if ($recentAttempts->isEmpty())
                        <x-ui.empty-state
                            :title="__('panel.nothing_recent_title')"
                            :description="__('panel.nothing_recent_body')"
                        />
                    @else
                        <div class="space-y-3">
                            @foreach ($recentAttempts as $attempt)
                                <a href="{{ $attempt->status === \App\Enums\ExamAttemptStatus::InProgress ? route('exams.attempts.show', $attempt) : route('exams.attempts.results', $attempt) }}" class="block rounded-2xl border border-border bg-white p-4 shadow-soft hover:border-accent/40">
                                    <p class="font-semibold text-navy">
                                        {{ $attempt->examType->name }}
                                        @if ($attempt->isDemo())
                                            <span class="text-xs font-medium text-navy-muted">{{ __('panel.demo_badge') }}</span>
                                        @endif
                                    </p>
                                    <p class="mt-1 text-sm text-navy-muted">
                                        {{ str_replace('_', ' ', $attempt->status->value) }}
                                        @if ($attempt->score !== null)
                                            · {{ $attempt->score }} / {{ $attempt->max_score }}
                                        @endif
                                    </p>
                                </a>
                            @endforeach
                        </div>
                    @endif
                    <a href="{{ route('exams.attempts.index') }}" class="mt-4 inline-block text-sm font-semibold text-accent hover:text-accent-dark">
                        {{ __('panel.practice_history') }} →
                    </a>
                </div>
            </section>
        </div>

        <section class="mt-10" aria-labelledby="quick-links-heading">
            <h2 id="quick-links-heading" class="text-lg font-semibold text-navy">{{ __('panel.quick_links') }}</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <a href="{{ route('mistakes.index') }}" class="rounded-2xl border border-border bg-surface p-5 transition hover:border-accent/40">
                    <p class="font-semibold text-navy">{{ __('panel.mistake_vault') }}</p>
                    <p class="mt-1 text-sm text-navy-muted">{{ __('mistakes.review_again') }}</p>
                </a>
                <a href="{{ route('learn.study-methods') }}" class="rounded-2xl border border-border bg-surface p-5 transition hover:border-accent/40">
                    <p class="font-semibold text-navy">{{ __('panel.study_methods') }}</p>
                    <p class="mt-1 text-sm text-navy-muted">{{ __('pages.learn.study_methods_desc') }}</p>
                </a>
                <a href="{{ route('learn.road-signs') }}" class="rounded-2xl border border-border bg-surface p-5 transition hover:border-accent/40">
                    <p class="font-semibold text-navy">{{ __('panel.road_signs') }}</p>
                    <p class="mt-1 text-sm text-navy-muted">{{ __('panel.open_signs') }}</p>
                </a>
                <a href="{{ route('learn.traffic-rules.pedestrian-priority') }}" class="rounded-2xl border border-border bg-surface p-5 transition hover:border-accent/40">
                    <p class="font-semibold text-navy">{{ __('home.resource_pedestrian') }}</p>
                    <p class="mt-1 text-sm text-navy-muted">{{ __('panel.educational_guides') }}</p>
                </a>
                <a href="{{ route('guides.index') }}" class="rounded-2xl border border-border bg-surface p-5 transition hover:border-accent/40">
                    <p class="font-semibold text-navy">{{ __('panel.study_guides') }}</p>
                    <p class="mt-1 text-sm text-navy-muted">{{ __('panel.admin_help') }}</p>
                </a>
                <a href="{{ route('profile.edit') }}" class="rounded-2xl border border-border bg-surface p-5 transition hover:border-accent/40">
                    <p class="font-semibold text-navy">{{ __('panel.account_settings') }}</p>
                    <p class="mt-1 text-sm text-navy-muted">{{ __('panel.profile_password') }}</p>
                </a>
            </div>
        </section>
    </div>
</x-layouts.dashboard>
