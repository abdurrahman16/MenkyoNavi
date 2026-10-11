<x-layouts.site
    :title="__('pages.guides.meta_title')"
    :description="__('pages.guides.meta_description')"
    :canonical="url()->current()"
>
    <x-ui.breadcrumb :items="[
        ['label' => __('pages.home'), 'url' => route('home')],
        ['label' => __('pages.guides.breadcrumb'), 'url' => route('guides.index')],
    ]" />

    <x-ui.page-hero
        :title="__('pages.guides.title')"
        :subtitle="__('pages.guides.subtitle')"
    />

    <section class="py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <p class="max-w-3xl leading-relaxed text-navy-muted">{{ __('pages.guides.intro') }}</p>

            <div class="mt-12 grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['title' => __('pages.guides.card_conversion_title'), 'desc' => __('pages.guides.card_conversion_desc'), 'route' => 'guides.foreign-license-conversion'],
                    ['title' => __('pages.guides.card_documents_title'), 'desc' => __('pages.guides.card_documents_desc'), 'route' => 'guides.required-documents'],
                    ['title' => __('pages.guides.card_centers_title'), 'desc' => __('pages.guides.card_centers_desc'), 'route' => 'guides.exam-centers'],
                ] as $guide)
                    <a
                        href="{{ route($guide['route']) }}"
                        class="group flex flex-col rounded-2xl border border-border bg-white p-6 shadow-soft transition hover:border-accent/40"
                    >
                        <h2 class="text-lg font-semibold text-navy group-hover:text-accent">{{ $guide['title'] }}</h2>
                        <p class="mt-2 flex-1 text-sm leading-relaxed text-navy-muted">{{ $guide['desc'] }}</p>
                        <span class="mt-4 text-sm font-semibold text-accent">{{ __('pages.explore') }}</span>
                    </a>
                @endforeach
            </div>

            <x-ui.ad-slot name="content-bottom" class="mt-12" />

            <div class="mt-12">
                <x-ui.disclaimer-notice />
            </div>
        </div>
    </section>
</x-layouts.site>
