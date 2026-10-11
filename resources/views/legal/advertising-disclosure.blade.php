@php
    $contactLink = '<a href="'.e(route('contact')).'" class="font-semibold text-accent hover:text-accent-dark">'.e(__('pages.contact.breadcrumb')).'</a>';
@endphp

<x-layouts.site
    :title="__('pages.advertising.meta_title')"
    :description="__('pages.advertising.meta_description')"
    :canonical="url()->current()"
>
    <x-ui.breadcrumb :items="[
        ['label' => __('pages.home'), 'url' => route('home')],
        ['label' => __('pages.advertising.breadcrumb'), 'url' => route('advertising-disclosure')],
    ]" />

    <x-ui.page-hero
        :title="__('pages.advertising.title')"
        :subtitle="__('pages.advertising.subtitle')"
    />

    <section class="section-pad">
        <div class="section-shell prose-menkyo max-w-3xl">
            <h2>{{ __('pages.advertising.s1_title') }}</h2>
            <p>{{ __('pages.advertising.s1_body') }}</p>
            <h2>{{ __('pages.advertising.s2_title') }}</h2>
            <p>{{ __('pages.advertising.s2_body') }}</p>
            <h2>{{ __('pages.advertising.s3_title') }}</h2>
            <p>{{ __('pages.advertising.s3_body') }}</p>
            <h2>{{ __('pages.advertising.s4_title') }}</h2>
            <p>{{ __('pages.advertising.s4_body') }}</p>
            <h2>{{ __('pages.advertising.s5_title') }}</h2>
            <p>{!! __('pages.advertising.s5_body', ['contact' => $contactLink]) !!}</p>
        </div>
    </section>
</x-layouts.site>
