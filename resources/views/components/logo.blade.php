@props([
    'variant' => 'full',
])

@php
    $variant = in_array($variant, ['full', 'mark'], true) ? $variant : 'full';
    $brand = config('menkyo.name', 'MenkyoNavi');
    $brandJa = config('menkyo.name_ja', '免許ナビ');
    $alt = $variant === 'mark'
        ? "{$brand} icon"
        : "{$brand} ({$brandJa}) logo";
    $src = $variant === 'mark'
        ? asset('images/fav.png')
        : asset('images/logo.png');
    $defaultClass = $variant === 'mark'
        ? 'h-10 w-10 object-contain'
        : 'h-10 w-auto max-h-11 object-contain object-left';
@endphp

<img
    {{ $attributes->class([$defaultClass, 'site-logo']) }}
    src="{{ $src }}"
    alt="{{ $alt }}"
    width="{{ $variant === 'mark' ? 40 : 196 }}"
    height="{{ $variant === 'mark' ? 40 : 44 }}"
    decoding="async"
/>
