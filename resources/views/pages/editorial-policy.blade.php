<x-layouts.site
    :title="__('pages.editorial.meta_title')"
    :description="__('pages.editorial.meta_description')"
    :canonical="url()->current()"
>
    <x-ui.breadcrumb :items="[
        ['label' => __('pages.home'), 'url' => route('home')],
        ['label' => __('pages.editorial.breadcrumb'), 'url' => route('editorial-policy')],
    ]" />

    <x-ui.page-hero
        :title="__('pages.editorial.title')"
        :subtitle="__('pages.editorial.subtitle')"
    />

    <section class="py-16 sm:py-20">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 space-y-10 text-navy-muted">
            @foreach ([1, 2, 3, 4] as $section)
                <section>
                    <h2 class="text-2xl font-semibold text-navy">{{ __('pages.editorial.s'.$section.'_title') }}</h2>
                    <p class="mt-4 leading-relaxed">{{ __('pages.editorial.s'.$section.'_body') }}</p>
                </section>
            @endforeach

            <p>
                <a href="{{ route('sources') }}" class="font-semibold text-accent hover:text-accent-dark">{{ __('pages.sources.title') }}</a>
                <span aria-hidden="true">&middot;</span>
                <a href="{{ route('contact') }}" class="font-semibold text-accent hover:text-accent-dark">{{ __('pages.contact.breadcrumb') }}</a>
            </p>
        </div>
    </section>
</x-layouts.site>
