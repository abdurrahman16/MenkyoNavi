<x-layouts.site
    :title="__('pages.documents.meta_title')"
    :description="__('pages.documents.meta_description')"
    :canonical="url()->current()"
>
    <x-ui.breadcrumb :items="[
        ['label' => __('pages.home'), 'url' => route('home')],
        ['label' => __('pages.guides.breadcrumb'), 'url' => route('guides.index')],
        ['label' => __('pages.documents.breadcrumb'), 'url' => route('guides.required-documents')],
    ]" />

    <x-ui.page-hero
        :title="__('pages.documents.title')"
        :subtitle="__('pages.documents.subtitle')"
    />

    <section class="py-16 sm:py-20">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <p class="leading-relaxed text-navy-muted">{{ __('pages.documents.intro') }}</p>

            <div class="mt-10 space-y-6">
                @foreach ([1, 2, 3, 4] as $section)
                    <div class="rounded-2xl border border-border bg-white p-6 shadow-soft">
                        <h2 class="text-xl font-semibold text-navy">{{ __('pages.documents.s'.$section.'_title') }}</h2>
                        <p class="mt-2 text-sm leading-relaxed text-navy-muted">{{ __('pages.documents.s'.$section.'_body') }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-10 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('guides.exam-centers') }}" class="btn-primary w-full sm:w-auto">{{ __('pages.documents.cta_centers') }}</a>
                <a href="{{ route('contact') }}" class="btn-secondary w-full sm:w-auto">{{ __('pages.documents.cta_contact') }}</a>
            </div>

            <div class="mt-12">
                <x-ui.disclaimer-notice />
            </div>
        </div>
    </section>
</x-layouts.site>
