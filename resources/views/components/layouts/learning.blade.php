@props([
    'sidebarLabel' => null,
])

{{--
    Shared public learning-page shell: main column (~2/3) + related resources (~1/3).
    items-start / self-start keep sidebar cards content-sized so they never stretch
    to the article height or visually collide with the site footer.
--}}
<section {{ $attributes->merge(['class' => 'section-pad section-paper']) }}>
    <div class="section-shell">
        <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-12 lg:gap-10">
            <div class="reveal min-w-0 space-y-8 lg:col-span-8" data-reveal>
                {{ $slot }}
            </div>

            <aside
                @class([
                    'reveal reveal-delay-2 min-w-0 w-full space-y-4 lg:col-span-4 lg:self-start',
                ])
                data-reveal
                @if (filled($sidebarLabel))
                    aria-label="{{ $sidebarLabel }}"
                @endif
            >
                {{ $sidebar }}
            </aside>
        </div>

        @isset($below)
            <div class="reveal mt-10 lg:mt-12" data-reveal>
                {{ $below }}
            </div>
        @endisset
    </div>
</section>
