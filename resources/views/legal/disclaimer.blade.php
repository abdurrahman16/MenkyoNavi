<x-layouts.site
    :title="__('pages.disclaimer.meta_title')"
    :description="__('pages.disclaimer.meta_description')"
    :canonical="url()->current()"
>
    <x-ui.breadcrumb :items="[
        ['label' => __('pages.home'), 'url' => route('home')],
        ['label' => __('pages.disclaimer.breadcrumb'), 'url' => route('disclaimer')],
    ]" />

    <x-ui.page-hero
        :title="__('pages.disclaimer.title')"
        :subtitle="__('pages.disclaimer.subtitle')"
    />

    <section class="py-16 sm:py-20">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 space-y-8 leading-relaxed text-navy-muted">
            @foreach ([1, 2, 3] as $section)
                <section>
                    <h2 class="text-xl font-semibold text-navy">{{ __('pages.disclaimer.s'.$section.'_title') }}</h2>
                    <p class="mt-3">{{ __('pages.disclaimer.s'.$section.'_body') }}</p>
                </section>
            @endforeach

            <x-ui.disclaimer-notice />
        </div>
    </section>
</x-layouts.site>
