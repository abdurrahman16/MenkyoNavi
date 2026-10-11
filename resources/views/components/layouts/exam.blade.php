@props([
    'title' => config('app.name'),
    'description' => null,
    'canonical' => null,
    'robots' => null,
    'ogImage' => null,
    'jsonLd' => null,
    'showLeftAd' => true,
    'showRightAd' => true,
    'showBottomAd' => true,
])

@php
    use App\Support\Advertising;

    $renderLeftAd = $showLeftAd && Advertising::shouldRenderSlot('exam-left');
    $renderRightAd = $showRightAd && Advertising::shouldRenderSlot('exam-right');
    $renderBottomAd = $showBottomAd && Advertising::shouldRenderSlot('exam-bottom');
@endphp

{{--
    Future examination shell: advertising regions only.
    Do not place ads beside answer controls, timers, or navigation inside the main region.
--}}
<x-layouts.site
    :title="$title"
    :description="$description"
    :canonical="$canonical"
    :robots="$robots"
    :og-image="$ogImage"
    :json-ld="$jsonLd"
>
    <div class="border-b border-border bg-gradient-to-b from-surface via-washi/40 to-surface py-5 sm:py-7">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div
                @class([
                    'grid grid-cols-1 gap-6 lg:gap-8',
                    'xl:grid-cols-[160px_minmax(0,1fr)_160px]' => $renderLeftAd || $renderRightAd,
                ])
            >
                @if ($renderLeftAd)
                    <aside class="hidden xl:block" aria-label="Left advertisement region">
                        <div class="sticky top-24">
                            <x-ui.ad-slot name="exam-left" class="mx-0 px-0 py-0" />
                        </div>
                    </aside>
                @elseif ($renderRightAd)
                    <div class="hidden xl:block" aria-hidden="true"></div>
                @endif

                <section
                    class="min-w-0 overflow-x-clip rounded-3xl border border-border bg-white p-4 shadow-soft sm:p-6 lg:p-8"
                    aria-label="Examination content"
                    data-exam-shell
                >
                    {{ $slot }}
                </section>

                @if ($renderRightAd)
                    <aside class="hidden xl:block" aria-label="Right advertisement region">
                        <div class="sticky top-24">
                            <x-ui.ad-slot name="exam-right" class="mx-0 px-0 py-0" />
                        </div>
                    </aside>
                @elseif ($renderLeftAd)
                    <div class="hidden xl:block" aria-hidden="true"></div>
                @endif
            </div>

            @if ($renderBottomAd)
                <div class="mt-6 lg:mt-8">
                    <x-ui.ad-slot name="exam-bottom" class="mx-0 px-0 py-0" />
                </div>
            @endif
        </div>
    </div>
</x-layouts.site>
