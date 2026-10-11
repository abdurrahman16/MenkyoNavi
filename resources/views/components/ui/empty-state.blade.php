@props([
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-dashed border-border bg-surface/50 px-6 py-10 text-center']) }}>
    <h3 class="text-lg font-semibold text-navy">{{ $title }}</h3>
    @if ($description)
        <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-navy-muted">{{ $description }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-6">
            {{ $slot }}
        </div>
    @endif
</div>
