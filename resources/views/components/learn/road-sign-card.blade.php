@props([
    'sign',
    'labels' => [],
    'sources' => [],
])

@php
    /** @var array<string, mixed> $sign */
    /** @var array<string, string> $labels */
    /** @var array<string, array<string, mixed>> $sources */

    $openLabel = $labels['open'] ?? __('road_signs.open_details');
    $closeLabel = $labels['close'] ?? __('road_signs.close_details');

    $categoryTone = match ($sign['category'] ?? '') {
        'regulatory' => 'border-vermilion/40 open:border-vermilion/70',
        'warning' => 'border-gold/50 open:border-gold/80',
        'instruction' => 'border-accent/45 open:border-accent/70',
        'guide' => 'border-accent/40 open:border-accent/65',
        'supplementary' => 'border-matcha/45 open:border-matcha/70',
        default => 'border-vermilion/30 open:border-vermilion/60',
    };

    $badgeTone = match ($sign['category'] ?? '') {
        'regulatory' => 'bg-vermilion/12 text-vermilion',
        'warning' => 'bg-gold/20 text-[#8a6528]',
        'instruction' => 'bg-accent/12 text-accent-dark',
        'guide' => 'bg-accent/12 text-accent-dark',
        'supplementary' => 'bg-matcha/15 text-matcha',
        default => 'bg-vermilion/10 text-vermilion',
    };

    $signSources = collect($sign['source_ids'] ?? [])
        ->map(fn (string $id): ?array => $sources[$id] ?? null)
        ->filter()
        ->values()
        ->all();
@endphp

<details
    id="sign-{{ $sign['id'] }}"
    {{ $attributes->class([
        'road-sign-card group scroll-mt-24 flex h-full flex-col rounded-3xl border bg-white p-4 shadow-soft transition duration-300 ease-menkyo open:shadow-lift sm:p-5',
        $categoryTone,
        'hover:-translate-y-0.5 hover:shadow-lift motion-reduce:transform-none',
    ]) }}
    data-road-sign-card="{{ $sign['id'] }}"
    data-category="{{ $sign['category'] }}"
    data-sign-details-card
>
    <summary class="cursor-pointer list-none marker:content-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent [&::-webkit-details-marker]:hidden">
        <div class="road-sign-card__art flex aspect-[4/3] items-center justify-center overflow-hidden rounded-2xl p-7 sm:p-8">
            <img
                src="{{ asset($sign['illustration']) }}"
                alt="{{ $sign['alt'] }}"
                width="200"
                height="200"
                class="box-border h-auto max-h-[8.25rem] w-full max-w-[8.25rem] object-contain p-1 transition duration-300 ease-menkyo group-hover:scale-[1.02] group-open:scale-100 motion-reduce:transform-none"
                loading="lazy"
                decoding="async"
            />
        </div>
        <div class="mt-3 flex min-h-0 flex-col">
            <p class="inline-flex w-fit rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badgeTone }}">
                {{ $sign['category_label'] }}
            </p>
            <h3 class="mt-2 text-lg font-bold leading-snug text-navy">{{ $sign['official_name_ja'] }}</h3>
            <p class="mt-1 text-sm font-semibold leading-snug text-navy/90">{{ $sign['title'] }}</p>
            <p class="mt-2 text-sm leading-relaxed text-navy/80">{{ $sign['meaning_short'] }}</p>
            <span class="mt-3 inline-flex min-h-10 items-center text-sm font-semibold text-accent group-open:hidden">
                {{ $openLabel }}
                <span class="ms-1" aria-hidden="true">▸</span>
            </span>
            <span class="mt-3 hidden min-h-10 items-center text-sm font-semibold text-accent group-open:inline-flex">
                {{ $closeLabel }}
                <span class="ms-1" aria-hidden="true">▾</span>
            </span>
        </div>
    </summary>

    <div class="mt-4 space-y-3 border-t border-border pt-4 text-sm leading-relaxed text-navy/85 sm:text-base" data-sign-detail-body>
        <p>
            <span class="font-semibold text-navy">{{ $labels['official_name'] ?? __('road_signs.official_name_label') }}:</span>
            {{ $sign['official_name_ja'] }}（{{ $sign['code'] }}）
        </p>
        <p>
            <span class="font-semibold text-navy">{{ $labels['category'] ?? __('road_signs.category_label') }}:</span>
            {{ $sign['category_official_ja'] }} / {{ $sign['category_label'] }}
        </p>
        <p>
            <span class="font-semibold text-navy">{{ $labels['meaning'] ?? __('road_signs.meaning_label') }}:</span>
            {{ $sign['meaning'] }}
        </p>
        <p class="rounded-2xl border border-matcha/25 bg-matcha/10 px-3 py-2.5">
            <span class="font-semibold text-navy">{{ $labels['must_do'] ?? __('road_signs.must_do_label') }}:</span>
            {{ $sign['must_do'] }}
        </p>
        <p>
            <span class="font-semibold text-navy">{{ $labels['example'] ?? __('road_signs.example_label') }}:</span>
            {{ $sign['example'] }}
        </p>
        @if (filled($sign['misconception']))
            <p class="rounded-2xl border border-vermilion/25 bg-vermilion/8 px-3 py-2.5">
                <span class="font-semibold text-vermilion">{{ $labels['misconception'] ?? __('road_signs.misconception_label') }}:</span>
                <span class="text-navy/85">{{ $sign['misconception'] }}</span>
            </p>
        @endif
        @if (filled($sign['related_topic']))
            <p>
                <span class="font-semibold text-navy">{{ $labels['related'] ?? __('road_signs.related_label') }}:</span>
                {{ $sign['related_topic'] }}
            </p>
        @endif
        @if ($signSources !== [])
            <div>
                <p class="font-semibold text-navy">{{ $labels['source'] ?? __('road_signs.source_label') }}</p>
                <ul class="mt-1.5 list-disc space-y-1 ps-5">
                    @foreach ($signSources as $source)
                        <li>
                            <a
                                href="{{ $source['url'] }}"
                                class="font-medium text-accent underline-offset-2 hover:underline"
                                rel="noopener noreferrer"
                                target="_blank"
                            >
                                {{ $source['title'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</details>
