<x-layouts.site
    :title="__('pages.exam_centers.meta_title')"
    :description="__('pages.exam_centers.meta_description')"
    :canonical="url()->current()"
>
    <x-ui.breadcrumb :items="[
        ['label' => __('pages.home'), 'url' => route('home')],
        ['label' => __('pages.guides.breadcrumb'), 'url' => route('guides.index')],
        ['label' => __('pages.exam_centers.breadcrumb'), 'url' => route('guides.exam-centers')],
    ]" />

    <x-ui.page-hero
        :title="__('pages.exam_centers.title')"
        :subtitle="__('pages.exam_centers.subtitle')"
    />

    <section class="py-16 sm:py-20">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <article class="space-y-8 text-navy-muted">
                @foreach ([1, 2, 3, 4] as $section)
                    <section>
                        <h2 class="text-2xl font-semibold text-navy">{{ __('pages.exam_centers.s'.$section.'_title') }}</h2>
                        <p class="mt-4 leading-relaxed">{{ __('pages.exam_centers.s'.$section.'_body') }}</p>
                    </section>
                @endforeach

                <div class="flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('guides.index') }}" class="btn-secondary w-full sm:w-auto">{{ __('pages.exam_centers.cta_guides') }}</a>
                    <a href="{{ route('exams.index') }}" class="btn-primary w-full sm:w-auto">{{ __('pages.exam_centers.cta_practice') }}</a>
                </div>
            </article>

            <div class="mt-12">
                <x-ui.disclaimer-notice />
            </div>
        </div>
    </section>
</x-layouts.site>
