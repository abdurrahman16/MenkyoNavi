<x-layouts.site
    :title="__('pages.gaimen.meta_title')"
    :description="__('pages.gaimen.meta_description')"
    :canonical="url()->current()"
>
    <x-ui.breadcrumb :items="[
        ['label' => __('pages.home'), 'url' => route('home')],
        ['label' => __('pages.exams.breadcrumb'), 'url' => route('exams.index')],
        ['label' => __('pages.gaimen.breadcrumb'), 'url' => route('exams.gaimen-kirikae')],
    ]" />

    <x-ui.page-hero
        compact
        :eyebrow="__('pages.gaimen.eyebrow')"
        :title="__('pages.gaimen.title')"
        :subtitle="__('pages.gaimen.subtitle')"
    />

    <section class="section-pad pt-6 sm:pt-8">
        <div class="section-shell space-y-8 lg:space-y-10">
            <x-exams.practice-panel :offer="$practice" class="motion-reveal-up" />

            <div class="grid gap-8 lg:grid-cols-12 lg:gap-10">
                <div class="min-w-0 space-y-8 lg:col-span-8">
                    <article class="rounded-3xl border border-border bg-white p-6 shadow-soft sm:p-8">
                        <h2 class="text-xl font-bold text-navy sm:text-2xl">{{ __('pages.gaimen.overview_title') }}</h2>
                        <p class="mt-4 leading-relaxed text-navy-muted">{{ __('pages.gaimen.overview_p1') }}</p>
                        <p class="mt-4 leading-relaxed text-navy-muted">{{ __('pages.gaimen.overview_p2') }}</p>
                    </article>

                    <article class="rounded-3xl border border-border bg-white p-6 shadow-soft sm:p-8">
                        <h2 class="text-xl font-bold text-navy sm:text-2xl">{{ __('pages.gaimen.who_title') }}</h2>
                        <ul class="mt-4 list-disc space-y-2 pl-5 text-navy-muted">
                            <li>{{ __('pages.gaimen.who_1') }}</li>
                            <li>{{ __('pages.gaimen.who_2') }}</li>
                            <li>{{ __('pages.gaimen.who_3') }}</li>
                        </ul>
                    </article>

                    <article class="rounded-3xl border border-border bg-white p-6 shadow-soft sm:p-8">
                        <h2 class="text-xl font-bold text-navy sm:text-2xl">{{ __('pages.gaimen.topics_title') }}</h2>
                        <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                            @foreach ([
                                __('pages.gaimen.topic_1'),
                                __('pages.gaimen.topic_2'),
                                __('pages.gaimen.topic_3'),
                                __('pages.gaimen.topic_4'),
                                __('pages.gaimen.topic_5'),
                                __('pages.gaimen.topic_6'),
                            ] as $topic)
                                <li class="rounded-2xl border border-border bg-surface px-4 py-3 text-sm text-navy-muted">{{ $topic }}</li>
                            @endforeach
                        </ul>
                    </article>
                </div>

                <aside class="space-y-5 lg:col-span-4">
                    <div class="rounded-3xl border border-border bg-white p-6 shadow-soft">
                        <h3 class="text-lg font-semibold text-navy">{{ __('pages.gaimen.resources_title') }}</h3>
                        <ul class="mt-4 space-y-3 text-sm">
                            <li><a href="{{ route('learn.road-signs') }}" class="font-medium text-accent hover:text-accent-dark">{{ __('pages.gaimen.link_signs') }}</a></li>
                            <li><a href="{{ route('learn.traffic-rules') }}" class="font-medium text-accent hover:text-accent-dark">{{ __('pages.gaimen.link_rules') }}</a></li>
                            <li><a href="{{ route('guides.foreign-license-conversion') }}" class="font-medium text-accent hover:text-accent-dark">{{ __('pages.gaimen.link_guide') }}</a></li>
                            <li><a href="{{ route('guides.exam-centers') }}" class="font-medium text-accent hover:text-accent-dark">{{ __('pages.gaimen.link_centers') }}</a></li>
                        </ul>
                    </div>
                    <x-ui.ad-slot name="sidebar" class="hidden lg:block" />
                </aside>
            </div>
        </div>
    </section>

    <x-ui.ad-slot name="content-bottom" class="section-shell py-4" />
</x-layouts.site>
