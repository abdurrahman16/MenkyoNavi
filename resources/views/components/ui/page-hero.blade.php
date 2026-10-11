@props([
    'title',
    'subtitle' => null,
    'eyebrow' => null,
    'compact' => false,
])

<section @class([
    'relative overflow-hidden border-b border-border gradient-hero',
    'py-8 sm:py-10' => $compact,
    'py-10 sm:py-12' => ! $compact,
])>
    <div class="section-shell relative">
        <div class="max-w-3xl">
            @if ($eyebrow)
                <p class="section-label">{{ $eyebrow }}</p>
            @endif
            <h1 @class([
                'font-bold tracking-tight text-navy text-balance',
                'mt-2 text-2xl sm:text-3xl' => $compact,
                'mt-2 text-3xl sm:text-4xl' => ! $compact,
                'mt-0' => ! $eyebrow,
            ])>
                {{ $title }}
            </h1>
            <div class="motion-divider" aria-hidden="true"></div>
            @if ($subtitle)
                <p @class([
                    'leading-relaxed text-navy-muted',
                    'mt-3 max-w-2xl text-base' => $compact,
                    'mt-4 max-w-2xl text-base sm:text-lg' => ! $compact,
                ])>
                    {{ $subtitle }}
                </p>
            @endif
            @if ($slot->isNotEmpty())
                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
                    {{ $slot }}
                </div>
            @endif
        </div>
    </div>
</section>
