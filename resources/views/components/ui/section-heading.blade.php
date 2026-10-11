@props([
    'title',
    'subtitle' => null,
    'label' => null,
])

<div {{ $attributes->merge(['class' => 'max-w-3xl']) }}>
    @if ($label)
        <p class="section-label mb-3">{{ $label }}</p>
    @endif
    <h2 class="text-2xl font-bold tracking-tight text-navy sm:text-[1.75rem] text-balance">
        {{ $title }}
    </h2>
    <div class="motion-divider" aria-hidden="true"></div>
    @if ($subtitle)
        <p class="mt-3 text-base leading-relaxed text-navy-muted sm:mt-4">
            {{ $subtitle }}
        </p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-4">
            {{ $slot }}
        </div>
    @endif
</div>
