@props([
    'imageKey',
    'alt' => '',
    'priority' => false,
    'sizes' => '(max-width: 640px) 100vw, (max-width: 1024px) 90vw, 720px',
    'objectPosition' => 'center',
])

@php
    $asset = \App\Support\Content\HumanImages::get($imageKey);
@endphp

@if ($asset)
    @php
        $srcset = collect($asset['srcset'] ?? [])
            ->map(fn (string $descriptor, string $path): string => asset($path).' '.$descriptor)
            ->implode(', ');
        $width = (int) ($asset['width'] ?? 1600);
        $height = (int) ($asset['height'] ?? 900);
    @endphp
    <picture {{ $attributes->only('class')->merge(['class' => 'block overflow-hidden']) }}>
        <source
            type="image/webp"
            srcset="{{ $srcset }}"
            sizes="{{ $sizes }}"
        >
        <img
            src="{{ asset($asset['fallback'] ?? $asset['path']) }}"
            alt="{{ $alt }}"
            width="{{ $width }}"
            height="{{ $height }}"
            @if ($priority)
                fetchpriority="high"
                loading="eager"
                decoding="async"
            @else
                loading="lazy"
                decoding="async"
            @endif
            class="h-full w-full object-cover"
            style="object-position: {{ $objectPosition }}; aspect-ratio: {{ $width }} / {{ $height }};"
        >
    </picture>
@endif
