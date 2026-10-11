@props([
    'title' => config('app.name'),
    'description' => null,
    'canonical' => null,
    'robots' => null,
    'ogImage' => null,
    'jsonLd' => null,
    'showSidebarAd' => true,
    'showInlineAd' => false,
    'showBottomAd' => true,
])

@php
    use App\Support\Advertising;

    $renderSidebarAd = $showSidebarAd && Advertising::shouldRenderSlot('sidebar');
    $renderInlineAd = $showInlineAd && Advertising::shouldRenderSlot('article-inline');
    $renderBottomAd = $showBottomAd && Advertising::shouldRenderSlot('content-bottom');
    $hasSidebarColumn = $renderSidebarAd || isset($sidebar);
@endphp

{{--
    Educational / article shell with optional advertising regions.
    Sidebars hide below the lg breakpoint; mobile may show bottom ads only.
--}}
<x-layouts.site
    :title="$title"
    :description="$description"
    :canonical="$canonical"
    :robots="$robots"
    :og-image="$ogImage"
    :json-ld="$jsonLd"
>
    <div class="py-10 sm:py-14">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div
                @class([
                    'grid grid-cols-1 items-start gap-8 lg:gap-10',
                    'lg:grid-cols-[minmax(0,1fr)_280px]' => $hasSidebarColumn,
                ])
            >
                <div class="min-w-0">
                    {{ $slot }}

                    @if ($renderInlineAd)
                        <div class="my-10">
                            <x-ui.ad-slot name="article-inline" class="mx-0 px-0 py-0" />
                        </div>
                    @endif

                    @isset($afterContent)
                        {{ $afterContent }}
                    @endisset
                </div>

                @if ($hasSidebarColumn)
                    <aside class="hidden min-w-0 self-start lg:block" aria-label="Article sidebar">
                        <div class="sticky top-24 space-y-6">
                            @isset($sidebar)
                                {{ $sidebar }}
                            @endisset
                            @if ($renderSidebarAd)
                                <x-ui.ad-slot name="sidebar" class="mx-0 px-0 py-0" />
                            @endif
                        </div>
                    </aside>
                @endif
            </div>

            @if ($renderBottomAd)
                <div class="mt-10 lg:mt-12">
                    <x-ui.ad-slot name="content-bottom" class="mx-0 px-0 py-0" />
                </div>
            @endif
        </div>
    </div>
</x-layouts.site>
