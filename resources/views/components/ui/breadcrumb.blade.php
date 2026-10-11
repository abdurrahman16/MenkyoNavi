@props([
    'items' => [],
])

@php
    $schemaItems = collect($items)->values()->map(function ($item, $index) {
        $entry = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $item['label'],
        ];

        if (! empty($item['url'])) {
            $entry['item'] = $item['url'];
        }

        return $entry;
    })->all();

    $breadcrumbLd = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $schemaItems,
    ];
@endphp

<x-seo.json-ld :data="$breadcrumbLd" />

<nav aria-label="{{ __('common.breadcrumb') }}" class="border-b border-border bg-white">
    <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
        <ol class="flex flex-wrap items-center gap-2 text-sm text-navy-muted">
            @foreach ($items as $index => $item)
                @if ($index > 0)
                    <li aria-hidden="true" class="text-border">/</li>
                @endif
                <li>
                    @if (! empty($item['url']) && $index < count($items) - 1)
                        <a
                            href="{{ $item['url'] }}"
                            class="font-medium hover:text-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                        >
                            {{ $item['label'] }}
                        </a>
                    @elseif (! empty($item['url']) && $index === count($items) - 1)
                        <span class="font-semibold text-navy" aria-current="page">{{ $item['label'] }}</span>
                    @else
                        <span class="font-semibold text-navy" aria-current="page">{{ $item['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
</nav>
