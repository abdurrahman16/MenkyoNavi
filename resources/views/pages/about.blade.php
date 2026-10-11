@php
    $contactLink = '<a href="'.e(route('contact')).'" class="font-semibold text-accent hover:text-accent-dark">'.e(__('pages.contact.breadcrumb')).'</a>';
@endphp

<x-layouts.site
    :title="__('pages.about.meta_title')"
    :description="__('pages.about.meta_description')"
    :canonical="url()->current()"
>
    <x-ui.breadcrumb :items="[
        ['label' => __('pages.home'), 'url' => route('home')],
        ['label' => __('pages.about.breadcrumb'), 'url' => route('about')],
    ]" />

    <x-ui.page-hero
        :title="__('pages.about.title')"
        :subtitle="__('pages.about.subtitle')"
    />

    <section class="py-16 sm:py-20">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 space-y-10 text-navy-muted">
            @foreach ([1, 2, 3, 4] as $section)
                <section>
                    <h2 class="text-2xl font-semibold text-navy">{{ __('pages.about.s'.$section.'_title') }}</h2>
                    <p class="mt-4 leading-relaxed">{!! __('pages.about.s'.$section.'_body', ['contact' => $contactLink]) !!}</p>
                </section>
            @endforeach

            <p>
                <a href="{{ route('editorial-policy') }}" class="font-semibold text-accent hover:text-accent-dark">{{ __('pages.editorial.breadcrumb') }}</a>
                <span aria-hidden="true">&middot;</span>
                <a href="{{ route('sources') }}" class="font-semibold text-accent hover:text-accent-dark">{{ __('pages.sources.breadcrumb') }}</a>
                <span aria-hidden="true">&middot;</span>
                <x-ui.contact-link />
            </p>

            <x-ui.disclaimer-notice />
        </div>
    </section>
</x-layouts.site>
