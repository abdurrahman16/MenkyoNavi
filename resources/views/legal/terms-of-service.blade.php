@php
    $contactLink = '<a href="'.e(route('contact')).'" class="font-semibold text-accent hover:text-accent-dark">'.e(__('pages.contact.breadcrumb')).'</a>';
@endphp

<x-layouts.site
    :title="__('pages.terms.meta_title')"
    :description="__('pages.terms.meta_description')"
    :canonical="url()->current()"
    :robots="'noindex, follow'"
>
    <x-ui.breadcrumb :items="[
        ['label' => __('pages.home'), 'url' => route('home')],
        ['label' => __('pages.terms.breadcrumb'), 'url' => route('terms-of-service')],
    ]" />

    <x-ui.page-hero
        :title="__('pages.terms.title')"
        :subtitle="__('pages.terms.subtitle')"
    />

    <section class="py-16 sm:py-20">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <article class="space-y-8 leading-relaxed text-navy-muted">
                @foreach ([1, 2, 3, 4, 5] as $section)
                    <section>
                        <h2 class="text-xl font-semibold text-navy">{{ __('pages.terms.s'.$section.'_title') }}</h2>
                        <p class="mt-3">{!! __('pages.terms.s'.$section.'_body', ['contact' => $contactLink]) !!}</p>
                    </section>
                @endforeach
            </article>
        </div>
    </section>
</x-layouts.site>
