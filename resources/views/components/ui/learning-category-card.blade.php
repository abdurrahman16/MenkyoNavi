@props([
    'title',
    'description',
    'href',
    'equalHeight' => false,
    'accent' => 'indigo',
])

@php
    $accents = [
        'indigo' => [
            'card' => 'learn-card--indigo',
            'badge' => 'bg-accent/15 text-accent',
            'cta' => 'text-accent group-hover:text-accent-dark',
            'bar' => 'from-accent to-accent-bright',
        ],
        'vermilion' => [
            'card' => 'learn-card--vermilion',
            'badge' => 'bg-vermilion/15 text-vermilion',
            'cta' => 'text-vermilion group-hover:text-vermilion',
            'bar' => 'from-vermilion to-gold',
        ],
        'gold' => [
            'card' => 'learn-card--gold',
            'badge' => 'bg-gold/20 text-navy',
            'cta' => 'text-navy group-hover:text-accent-dark',
            'bar' => 'from-gold to-accent',
        ],
        'matcha' => [
            'card' => 'learn-card--matcha',
            'badge' => 'bg-matcha/15 text-matcha',
            'cta' => 'text-matcha group-hover:text-navy',
            'bar' => 'from-matcha to-accent',
        ],
        'warm' => [
            'card' => 'learn-card--warm',
            'badge' => 'bg-vermilion/12 text-vermilion',
            'cta' => 'text-vermilion group-hover:text-navy',
            'bar' => 'from-vermilion to-gold',
        ],
    ];
    $tone = $accents[$accent] ?? $accents['indigo'];
@endphp

<a
    href="{{ $href }}"
    {{ $attributes->class([
        'card-interactive group relative flex flex-col overflow-hidden p-5 sm:p-6',
        $tone['card'],
        'h-full' => $equalHeight,
    ]) }}
>
    <span class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r {{ $tone['bar'] }}" aria-hidden="true"></span>
    <h3 class="text-lg font-semibold text-navy group-hover:text-accent break-words">{{ $title }}</h3>
    <p @class([
        'mt-2 text-sm leading-relaxed text-navy-muted break-words',
        'flex-1' => $equalHeight,
    ])>{{ $description }}</p>
    <span class="mt-4 inline-flex min-h-11 items-center gap-1 text-sm font-semibold {{ $tone['cta'] }}">
        {{ __('pages.explore') }}
        <span aria-hidden="true" class="transition duration-300 group-hover:translate-x-1 motion-reduce:transform-none">→</span>
    </span>
</a>
