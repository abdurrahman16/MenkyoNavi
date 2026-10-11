@props([
    'title',
    'description',
    'href',
    'icon' => 'book',
    'accent' => 'blue',
    'equalHeight' => true,
])

@php
    $accents = [
        'blue' => 'bg-accent/15 text-accent',
        'teal' => 'bg-matcha/15 text-matcha',
        'amber' => 'bg-gold/20 text-navy',
        'navy' => 'bg-accent/10 text-navy',
        'vermilion' => 'bg-vermilion/15 text-vermilion',
        'matcha' => 'bg-matcha/15 text-matcha',
        'gold' => 'bg-gold/20 text-navy',
        'warm' => 'bg-vermilion/12 text-vermilion',
    ];
    $tone = $accents[$accent] ?? $accents['blue'];

    $cardTints = [
        'blue' => 'learn-card--indigo',
        'navy' => 'learn-card--indigo',
        'teal' => 'learn-card--matcha',
        'matcha' => 'learn-card--matcha',
        'amber' => 'learn-card--gold',
        'gold' => 'learn-card--gold',
        'vermilion' => 'learn-card--vermilion',
        'warm' => 'learn-card--warm',
    ];
    $cardTint = $cardTints[$accent] ?? '';

    $icons = [
        'book' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 19.5A2.5 2.5 0 016.5 17H20M4 19.5A2.5 2.5 0 016.5 22H20V2H6.5A2.5 2.5 0 004 4.5v15z" />',
        'sign' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 3v18M5 8h14l-2 4H7l-2-4zM5 16h10" />',
        'globe' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 21a9 9 0 100-18 9 9 0 000 18zM3 12h18M12 3c2.5 2.8 3.8 5.8 3.8 9S14.5 18.2 12 21C9.5 18.2 8.2 15.2 8.2 12S9.5 5.8 12 3z" />',
        'docs' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6M9 16h6M8 4h5l5 5v11a2 2 0 01-2 2H8a2 2 0 01-2-2V6a2 2 0 012-2z" />',
        'map' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 4l-5 2v14l5-2 6 2 5-2V4l-5 2-6-2zM9 4v14M15 6v14" />',
        'alert' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 9v4m0 4h.01M10.3 4.7L2.8 18a2 2 0 001.7 3h15a2 2 0 001.7-3L13.7 4.7a2 2 0 00-3.4 0z" />',
    ];
    $iconMarkup = $icons[$icon] ?? $icons['book'];
@endphp

<a
    href="{{ $href }}"
    {{ $attributes->class([
        'card-interactive group flex flex-col p-5 sm:p-6',
        $cardTint,
        'h-full' => $equalHeight,
    ]) }}
>
    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $tone }}">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
            {!! $iconMarkup !!}
        </svg>
    </div>
    <h3 class="mt-4 text-lg font-semibold text-navy group-hover:text-accent break-words">{{ $title }}</h3>
    <p @class([
        'mt-2 text-sm leading-relaxed text-navy-muted break-words',
        'flex-1' => $equalHeight,
    ])>{{ $description }}</p>
    <span class="mt-4 inline-flex min-h-11 items-center gap-1 text-sm font-semibold text-accent">
        {{ __('pages.explore') }}
        <span aria-hidden="true" class="transition duration-300 group-hover:translate-x-1 motion-reduce:transform-none">→</span>
    </span>
</a>
