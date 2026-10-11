<x-layouts.site
    :title="__('pages.sources.meta_title')"
    :description="__('pages.sources.meta_description')"
    :canonical="url()->current()"
>
    <x-ui.breadcrumb :items="[
        ['label' => __('pages.home'), 'url' => route('home')],
        ['label' => __('pages.sources.breadcrumb'), 'url' => route('sources')],
    ]" />

    <x-ui.page-hero
        :title="__('pages.sources.title')"
        :subtitle="__('pages.sources.subtitle')"
    />

    <section class="py-16 sm:py-20">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="space-y-8">
                @foreach ([1, 2, 3] as $section)
                    <div class="rounded-2xl border border-border bg-white p-6 shadow-soft">
                        <h2 class="text-xl font-semibold text-navy">{{ __('pages.sources.s'.$section.'_title') }}</h2>
                        <p class="mt-2 text-sm leading-relaxed text-navy-muted">{{ __('pages.sources.s'.$section.'_body') }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-12">
                <x-ui.disclaimer-notice />
            </div>
        </div>
    </section>
</x-layouts.site>
