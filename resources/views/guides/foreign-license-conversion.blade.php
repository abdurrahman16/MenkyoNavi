<x-layouts.site
    :title="__('pages.foreign_license.meta_title')"
    :description="__('pages.foreign_license.meta_description')"
    :canonical="url()->current()"
>
    <x-ui.breadcrumb :items="[
        ['label' => __('pages.home'), 'url' => route('home')],
        ['label' => __('pages.guides.breadcrumb'), 'url' => route('guides.index')],
        ['label' => __('pages.foreign_license.breadcrumb'), 'url' => route('guides.foreign-license-conversion')],
    ]" />

    <x-ui.page-hero
        :title="__('pages.foreign_license.title')"
        :subtitle="__('pages.foreign_license.subtitle')"
    />

    <section class="py-16 sm:py-20">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <article class="space-y-8 text-navy-muted">
                @foreach ([1, 2, 3, 4] as $section)
                    <section>
                        <h2 class="text-2xl font-semibold text-navy">{{ __('pages.foreign_license.s'.$section.'_title') }}</h2>
                        <p class="mt-4 leading-relaxed">{{ __('pages.foreign_license.s'.$section.'_body') }}</p>
                    </section>
                @endforeach

                <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    <a href="{{ route('exams.gaimen-kirikae') }}" class="btn-primary w-full sm:w-auto">{{ __('pages.foreign_license.cta_gaimen') }}</a>
                    <a href="{{ route('guides.required-documents') }}" class="btn-secondary w-full sm:w-auto">{{ __('pages.foreign_license.cta_documents') }}</a>
                    <a href="{{ route('guides.exam-centers') }}" class="btn-secondary w-full sm:w-auto">{{ __('pages.foreign_license.cta_centers') }}</a>
                </div>
            </article>

            <div class="mt-12">
                <x-ui.disclaimer-notice />
            </div>
        </div>
    </section>
</x-layouts.site>
