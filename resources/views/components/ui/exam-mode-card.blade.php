@props([
    'title',
    'subtitle' => null,
    'japanese' => null,
    'description',
    'href',
    'icon' => 'honmen',
    'accent' => 'blue',
    'cta' => 'Open study hub',
])

@php
    $accents = [
        'teal' => [
            'ring' => 'hover:border-teal/50',
            'badge' => 'bg-teal/10 text-teal',
            'icon' => 'bg-gradient-to-br from-teal to-teal/80 text-white',
            'cta' => 'text-teal group-hover:text-teal',
            'bar' => 'from-teal to-accent-bright',
        ],
        'amber' => [
            'ring' => 'hover:border-amber/50',
            'badge' => 'bg-amber-soft text-navy',
            'icon' => 'bg-gradient-to-br from-amber to-amber/80 text-white',
            'cta' => 'text-amber group-hover:text-navy',
            'bar' => 'from-amber to-accent',
        ],
        'blue' => [
            'ring' => 'hover:border-accent/50',
            'badge' => 'bg-accent/10 text-accent',
            'icon' => 'bg-gradient-to-br from-accent to-accent-bright text-white',
            'cta' => 'text-accent group-hover:text-accent-dark',
            'bar' => 'from-accent to-accent-bright',
        ],
    ];
    $tone = $accents[$accent] ?? $accents['blue'];

    $icons = [
        'gaimen' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3.5 17.5h17M5 17.5l1.2-4.2A2 2 0 018.1 12h7.8a2 2 0 011.9 1.3L19 17.5M7 17.5a1.5 1.5 0 103 0m7 0a1.5 1.5 0 103 0M8 12l1-4h6l1 4" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M14.5 7.5l2-2.5M16.5 5h3" />',
        'karimen' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 20h8M12 4v12M9 8h6" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M7 18h10a1 1 0 011 1v1H6v-1a1 1 0 011-1z" />',
        'honmen' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 19l4.5-9 3.5 5 3-4L20 19H4z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 8h8" />',
    ];
    $iconMarkup = $icons[$icon] ?? $icons['honmen'];
@endphp

<a
    href="{{ $href }}"
    {{ $attributes->merge(['class' => 'group relative flex h-full flex-col overflow-hidden rounded-3xl border border-border bg-paper p-6 shadow-soft transition duration-300 ease-menkyo hover:-translate-y-1.5 hover:border-accent/45 hover:shadow-lift focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent motion-reduce:transform-none '.$tone['ring']]) }}
>
    <span class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r {{ $tone['bar'] }}" aria-hidden="true"></span>

    <div class="flex items-start justify-between gap-3">
        <div class="flex h-14 w-14 items-center justify-center rounded-2xl {{ $tone['icon'] }} shadow-soft">
            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                {!! $iconMarkup !!}
            </svg>
        </div>
        @if ($japanese)
            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $tone['badge'] }}">{{ $japanese }}</span>
        @endif
    </div>

    <h3 class="mt-5 text-xl font-bold tracking-tight text-navy">{{ $title }}</h3>
    @if ($subtitle)
        <p class="mt-1 text-sm font-medium text-navy-muted">{{ $subtitle }}</p>
    @endif
    <p class="mt-3 flex-1 text-sm leading-relaxed text-navy-muted">{{ $description }}</p>
    <span class="mt-5 inline-flex items-center gap-1 text-sm font-semibold {{ $tone['cta'] }}">
        {{ $cta }}
        <span aria-hidden="true" class="transition duration-300 group-hover:translate-x-1 motion-reduce:transform-none">→</span>
    </span>
</a>
