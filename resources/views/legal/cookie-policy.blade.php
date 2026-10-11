<x-layouts.site
    :title="__('pages.cookies.meta_title')"
    :description="__('pages.cookies.meta_description')"
    :canonical="url()->current()"
>
    <x-ui.breadcrumb :items="[
        ['label' => __('pages.home'), 'url' => route('home')],
        ['label' => __('pages.cookies.breadcrumb'), 'url' => route('cookie-policy')],
    ]" />

    <x-ui.page-hero
        :title="__('pages.cookies.title')"
        :subtitle="__('pages.cookies.subtitle')"
    />

    <section class="section-pad">
        <div class="section-shell prose-menkyo max-w-3xl">
            <h2>{{ __('pages.cookies.s1_title') }}</h2>
            <p>{{ __('pages.cookies.s1_body') }}</p>
            <h2>{{ __('pages.cookies.s2_title') }}</h2>
            <p>{{ __('pages.cookies.s2_body') }}</p>
            <h2>{{ __('pages.cookies.s3_title') }}</h2>
            <p>{{ __('pages.cookies.s3_body') }}</p>
            <h2>{{ __('pages.cookies.s4_title') }}</h2>
            <p>{{ __('pages.cookies.s4_body') }}</p>
            <p class="mt-8 text-sm">
                {{ __('pages.contact_label') }}: <x-ui.contact-link />
            </p>
        </div>
    </section>
</x-layouts.site>
