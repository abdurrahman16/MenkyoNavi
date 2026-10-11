@props([
    'assetKey',
    'assets' => [],
    'captions' => [],
    'alts' => [],
    'figureLabel' => null,
    'figureNumber' => null,
    'priority' => false,
    'class' => '',
])

@php
    $meta = $assets[$assetKey] ?? null;
    $path = is_array($meta) ? ($meta['path'] ?? null) : (is_string($meta) ? $meta : null);
    $width = is_array($meta) ? (int) ($meta['width'] ?? 840) : 840;
    $height = is_array($meta) ? (int) ($meta['height'] ?? 420) : 420;
    $caption = $captions[$assetKey] ?? '';
    $alt = $alts[$assetKey] ?? $caption;
@endphp

@if ($path)
    <figure {{ $attributes->class(['overflow-hidden rounded-3xl border border-border bg-paper shadow-soft', $class]) }} data-guide-figure="{{ $assetKey }}">
        <img
            src="{{ asset($path) }}"
            alt="{{ $alt }}"
            width="{{ $width }}"
            height="{{ $height }}"
            class="h-auto w-full object-contain bg-washi/40"
            @if ($priority)
                decoding="async"
                fetchpriority="high"
            @else
                loading="lazy"
                decoding="async"
            @endif
        />
        @if ($caption !== '')
            <figcaption class="border-t border-border px-4 py-3 text-sm leading-relaxed text-navy/75">
                @if ($figureLabel && $figureNumber)
                    <span class="font-semibold text-navy">{{ $figureLabel }} {{ $figureNumber }}. </span>
                @endif
                {{ $caption }}
            </figcaption>
        @endif
    </figure>
@endif
