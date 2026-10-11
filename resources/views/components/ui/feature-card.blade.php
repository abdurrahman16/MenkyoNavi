@props([
    'title',
    'description',
    'icon' => 'clipboard-list',
])

@php
    $icons = [
        'clipboard-list' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />',
        'signpost' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 6l4 4-4 4M3 10h18M7 18l-4-4 4-4" />',
        'archive' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />',
        'book-open' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />',
        'layout-dashboard' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v2a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10-2a1 1 0 011-1h4a1 1 0 011 1v6a1 1 0 01-1 1h-4a1 1 0 01-1-1v-6z" />',
        'smartphone' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />',
        'languages' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 5h12M9 3v2m4 13l4-8 4 8M10.5 17h7M5 19l3-7 3 7" />',
        'furigana' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 19h6M7 19V9m0 0H4m3 0h3M14 19h6v-2a3 3 0 00-3-3h0a3 3 0 00-3 3v2zm0-8h6" />',
        'exam' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />',
        'chart' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 19V5m0 14h16M8 15v4m4-8v8m4-12v12" />',
    ];
    $iconMarkup = $icons[$icon] ?? $icons['clipboard-list'];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-border bg-white p-6 shadow-soft transition duration-200 hover:-translate-y-0.5 hover:shadow-md motion-reduce:transform-none']) }}>
    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-surface text-accent">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
            {!! $iconMarkup !!}
        </svg>
    </div>
    <h3 class="mt-4 text-lg font-semibold text-navy">{{ $title }}</h3>
    <p class="mt-2 text-sm leading-relaxed text-navy-muted">{{ $description }}</p>
</div>
