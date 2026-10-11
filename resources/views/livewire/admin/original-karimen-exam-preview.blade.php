<div class="py-6 sm:py-8">
    <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-navy-muted">{{ __('admin.internal_editorial') }}</p>
                <h1 class="text-2xl font-bold text-navy">{{ __('admin.karimen_preview_title') }}</h1>
                <p class="mt-1 text-sm text-navy-muted">{{ __('admin.karimen_preview_subtitle') }}</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <a href="{{ route('admin.questions.original-karimen-pilot') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-border bg-white px-3 py-2 text-sm font-semibold text-navy">{{ __('admin.pilot_accuracy_review_title') }}</a>
                <a href="{{ route('admin.questions.dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-border bg-white px-3 py-2 text-sm font-semibold text-navy">{{ __('admin.dashboard_title') }}</a>
            </div>
        </div>

        <div class="rounded-2xl border-2 border-amber-500 bg-amber-50 px-4 py-4 text-amber-950" role="note" data-admin-preview-notice>
            <p class="text-base font-bold">管理者用・未承認問題の試験プレビュー</p>
            <p class="mt-2 text-sm leading-relaxed">{{ __('admin.karimen_preview_disclaimer') }}</p>
        </div>

        @if ($statusMessage !== '')
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900" role="status">{{ $statusMessage }}</div>
        @endif

        <section class="rounded-2xl border border-border bg-white p-5 space-y-3 text-sm">
            <h2 class="text-lg font-semibold text-navy">{{ __('admin.karimen_preview_status_heading') }}</h2>
            <dl class="grid gap-2 sm:grid-cols-2">
                <div><dt class="text-xs text-navy-muted">{{ __('admin.karimen_preview_staged') }}</dt><dd class="font-semibold text-navy">{{ $previewReadiness['count'] }} / 50</dd></div>
                <div><dt class="text-xs text-navy-muted">{{ __('admin.karimen_preview_clarification') }}</dt><dd class="font-semibold text-navy">{{ count($previewReadiness['clarification_ids']) }}</dd></div>
                <div><dt class="text-xs text-navy-muted">{{ __('admin.karimen_preview_human_accepted') }}</dt><dd class="font-semibold text-navy">{{ $releaseReport['human_accuracy_accepted_count'] }} / 50</dd></div>
                <div><dt class="text-xs text-navy-muted">{{ __('admin.karimen_preview_public') }}</dt><dd class="font-semibold text-navy">{{ $releaseReport['japanese_public_karimen_can_start'] ? __('admin.pilot_status_published') : __('admin.pilot_status_unpublished') }}</dd></div>
            </dl>
            @if ($previewReadiness['clarification_ids'] !== [])
                <p class="text-xs text-amber-900">{{ __('admin.karimen_preview_clarification_ids') }}: {{ implode(', ', $previewReadiness['clarification_ids']) }}</p>
            @endif
        </section>

        <section class="rounded-2xl border border-border bg-white p-5 space-y-4">
            <h2 class="text-lg font-semibold text-navy">{{ __('admin.karimen_preview_actions') }}</h2>
            <p class="text-sm text-navy-muted">{{ __('admin.karimen_preview_actions_help') }}</p>
            <div class="flex flex-col gap-2 sm:flex-row">
                @if ($openAttempt)
                    <a href="{{ route('exams.attempts.show', $openAttempt) }}" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-navy px-4 py-2 text-sm font-semibold text-white">{{ __('admin.karimen_preview_resume') }}</a>
                    <button type="button" wire:click="restartPreview" wire:confirm="{{ __('admin.karimen_preview_restart_confirm') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-amber-400 bg-amber-50 px-4 py-2 text-sm font-semibold text-navy">{{ __('admin.karimen_preview_restart') }}</button>
                @else
                    <button type="button" wire:click="startPreview" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-navy px-4 py-2 text-sm font-semibold text-white" @disabled(! $previewReadiness['ready'])>{{ __('admin.karimen_preview_start') }}</button>
                @endif
            </div>
            @unless ($previewReadiness['ready'])
                <p class="text-sm text-red-800">{{ __('admin.karimen_preview_not_ready') }}</p>
            @endunless
        </section>

        @if ($recentPreviews->isNotEmpty())
            <section class="rounded-2xl border border-border bg-white p-5 space-y-3">
                <h2 class="text-lg font-semibold text-navy">{{ __('admin.karimen_preview_history') }}</h2>
                <ul class="space-y-2 text-sm">
                    @foreach ($recentPreviews as $attempt)
                        <li class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-border/70 px-3 py-2">
                            <span class="text-navy">
                                {{ $attempt->started_at?->timezone(config('app.timezone'))?->format('Y-m-d H:i') }}
                                · {{ $attempt->status->value }}
                                @if ($attempt->score !== null)
                                    · {{ $attempt->score }} / {{ $attempt->max_score }}
                                @endif
                            </span>
                            <a href="{{ route($attempt->status === \App\Enums\ExamAttemptStatus::InProgress ? 'exams.attempts.show' : 'exams.attempts.results', $attempt) }}" class="font-semibold text-accent underline">{{ __('admin.pilot_col_action') }}</a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</div>
