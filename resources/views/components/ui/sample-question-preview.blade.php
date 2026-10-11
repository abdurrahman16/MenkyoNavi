@php
    $selected = $selected ?? null;
@endphp

<div
    {{ $attributes->merge(['class' => 'overflow-hidden rounded-3xl border border-border bg-paper shadow-card']) }}
    x-data="{ selected: null }"
>
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-border bg-washi px-4 py-3 sm:px-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-accent">{{ __('home.sample_preview_label') }}</p>
        <span class="rounded-full bg-amber-soft px-2.5 py-1 text-[11px] font-semibold text-navy">{{ __('home.sample_preview_only') }}</span>
    </div>

    <div class="space-y-4 p-5 sm:p-6">
        <p class="text-lg font-semibold leading-relaxed text-navy sm:text-xl">
            {{ __('home.sample_question_text') }}
        </p>
        <p class="text-sm leading-relaxed text-navy-muted">
            {{ __('home.sample_explanation') }}
        </p>
    </div>

    <div class="flex flex-col gap-3 border-t border-border bg-washi/80 p-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <p class="text-sm text-navy-muted">{{ __('home.sample_controls_note') }}</p>
        <div class="flex w-full gap-3 sm:w-auto">
            <button
                type="button"
                class="min-h-11 min-w-0 flex-1 rounded-2xl bg-matcha px-4 py-2.5 text-sm font-semibold text-white transition duration-300 hover:opacity-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-matcha active:translate-y-px sm:min-w-[7rem] sm:flex-none motion-reduce:transform-none"
                :class="{ 'ring-2 ring-offset-2 ring-matcha': selected === true }"
                @click="selected = true"
            >
                {{ __('home.sample_true') }}
            </button>
            <button
                type="button"
                class="min-h-11 min-w-0 flex-1 rounded-2xl bg-vermilion px-4 py-2.5 text-sm font-semibold text-white transition duration-300 hover:opacity-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-vermilion active:translate-y-px sm:min-w-[7rem] sm:flex-none motion-reduce:transform-none"
                :class="{ 'ring-2 ring-offset-2 ring-vermilion': selected === false }"
                @click="selected = false"
            >
                {{ __('home.sample_false') }}
            </button>
        </div>
    </div>
</div>
