@php
    $contactLink = '<a href="'.e(route('contact')).'" class="font-semibold text-accent hover:text-accent-dark">'.e(__('pages.contact.breadcrumb')).'</a>';
@endphp

<x-layouts.site
    :title="__('pages.privacy.meta_title')"
    :description="__('pages.privacy.meta_description')"
    :canonical="url()->current()"
    :robots="'noindex, follow'"
>
    <x-ui.breadcrumb :items="[
        ['label' => __('pages.home'), 'url' => route('home')],
        ['label' => __('pages.privacy.breadcrumb'), 'url' => route('privacy-policy')],
    ]" />

    <x-ui.page-hero
        :title="__('pages.privacy.title')"
        :subtitle="__('pages.privacy.subtitle')"
    />

    <section class="py-16 sm:py-20">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-amber-300 bg-amber-50 p-6 text-sm text-amber-950" role="note">
                <p>{{ __('pages.privacy.draft_note') }}</p>
            </div>

            <article class="mt-10 space-y-8 leading-relaxed text-navy-muted">
                @foreach ([1, 2, 3, 4, 5, 6] as $section)
                    <section>
                        <h2 class="text-xl font-semibold text-navy">{{ __('pages.privacy.s'.$section.'_title') }}</h2>
                        <p class="mt-3">{!! __('pages.privacy.s'.$section.'_body', ['contact' => $contactLink]) !!}</p>
                    </section>
                @endforeach
            </article>
        </div>
    </section>
</x-layouts.site>
