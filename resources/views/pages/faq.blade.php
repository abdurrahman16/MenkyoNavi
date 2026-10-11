<x-layouts.site
    :title="__('pages.faq_page.meta_title')"
    :description="__('pages.faq_page.meta_description')"
    :canonical="url()->current()"
>
    <x-ui.breadcrumb :items="[
        ['label' => __('pages.home'), 'url' => route('home')],
        ['label' => __('pages.faq_page.breadcrumb'), 'url' => route('faq')],
    ]" />

    <x-ui.page-hero
        :title="__('pages.faq_page.title')"
        :subtitle="__('pages.faq_page.subtitle')"
    />

    <section class="py-16 sm:py-20">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <livewire:faq-accordion />
        </div>
    </section>
</x-layouts.site>
