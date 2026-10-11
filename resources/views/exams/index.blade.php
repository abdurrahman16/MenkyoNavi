<x-layouts.site
    :title="__('pages.exams.meta_title')"
    :description="__('pages.exams.meta_description')"
    :canonical="url()->current()"
>
    <x-ui.breadcrumb :items="[
        ['label' => __('pages.home'), 'url' => route('home')],
        ['label' => __('pages.exams.breadcrumb'), 'url' => route('exams.index')],
    ]" />

    <x-ui.page-hero
        compact
        :eyebrow="__('pages.exams.eyebrow')"
        :title="__('pages.exams.title')"
        :subtitle="__('pages.exams.subtitle')"
    >
        <a href="#exam-modes" class="btn-primary w-full sm:w-auto">{{ __('pages.exams.cta_modes') }}</a>
        <a href="{{ route('learn.index') }}" class="btn-secondary w-full sm:w-auto">{{ __('pages.exams.cta_learn') }}</a>
    </x-ui.page-hero>

    <section id="exam-modes" class="section-pad">
        <div class="section-shell">
            <div class="grid gap-5 md:grid-cols-3 md:gap-6">
                <x-ui.exam-mode-card
                    :title="__('home.gaimen_title')"
                    :subtitle="__('home.gaimen_subtitle')"
                    :japanese="config('menkyo.exam_labels.gaimen')"
                    :description="__('home.gaimen_description')"
                    :href="route('exams.gaimen-kirikae')"
                    icon="gaimen"
                    accent="teal"
                    :cta="__('pages.exams.gaimen_cta')"
                />
                <x-ui.exam-mode-card
                    :title="__('home.karimen_title')"
                    :subtitle="__('home.karimen_subtitle')"
                    :japanese="config('menkyo.exam_labels.karimen')"
                    :description="__('home.karimen_description')"
                    :href="route('exams.karimen')"
                    icon="karimen"
                    accent="amber"
                    :cta="__('pages.exams.karimen_cta')"
                />
                <x-ui.exam-mode-card
                    :title="__('home.honmen_title')"
                    :subtitle="__('home.honmen_subtitle')"
                    :japanese="config('menkyo.exam_labels.honmen')"
                    :description="__('home.honmen_description')"
                    :href="route('exams.honmen')"
                    icon="honmen"
                    accent="blue"
                    :cta="__('pages.exams.honmen_cta')"
                />
            </div>
        </div>
    </section>

    <section class="border-y border-border bg-white section-pad">
        <div class="section-shell">
            <x-ui.section-heading
                :title="__('pages.exams.journey_title')"
                :subtitle="__('pages.exams.journey_subtitle')"
            />
            <ol class="mt-8 grid gap-4 sm:grid-cols-3">
                @foreach ([
                    ['n' => '1', 't' => __('pages.exams.journey_1_title'), 'd' => __('pages.exams.journey_1_body')],
                    ['n' => '2', 't' => __('pages.exams.journey_2_title'), 'd' => __('pages.exams.journey_2_body')],
                    ['n' => '3', 't' => __('pages.exams.journey_3_title'), 'd' => __('pages.exams.journey_3_body')],
                ] as $step)
                    <li class="rounded-3xl border border-border bg-surface p-5">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-accent text-sm font-bold text-white">{{ $step['n'] }}</span>
                        <h3 class="mt-3 text-lg font-semibold text-navy">{{ $step['t'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-navy-muted">{{ $step['d'] }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <x-ui.ad-slot name="content-mid" class="section-shell py-4" />

    <section class="section-pad">
        <div class="section-shell">
            <x-ui.section-heading
                :title="__('pages.exams.resources_title')"
                :subtitle="__('pages.exams.resources_subtitle')"
            />
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-ui.resource-card :title="__('home.resource_road_signs')" :description="__('home.resource_road_signs_desc')" :href="route('learn.road-signs')" icon="sign" accent="amber" />
                <x-ui.resource-card :title="__('home.resource_traffic_rules')" :description="__('home.resource_traffic_rules_desc')" :href="route('learn.traffic-rules')" icon="book" accent="blue" />
                <x-ui.resource-card :title="__('home.resource_hazard')" :description="__('home.resource_hazard_desc')" :href="route('learn.hazard-prediction')" icon="alert" accent="teal" />
                <x-ui.resource-card :title="__('home.resource_foreign_license')" :description="__('home.resource_foreign_license_desc')" :href="route('guides.foreign-license-conversion')" icon="globe" accent="teal" />
                <x-ui.resource-card :title="__('home.resource_documents')" :description="__('home.resource_documents_desc')" :href="route('guides.required-documents')" icon="docs" accent="navy" />
                <x-ui.resource-card :title="__('home.resource_centers')" :description="__('home.resource_centers_desc')" :href="route('guides.exam-centers')" icon="map" accent="blue" />
            </div>
        </div>
    </section>

    <section class="border-t border-border bg-surface section-pad">
        <div class="section-shell grid gap-8 lg:grid-cols-2 lg:items-start">
            <div class="min-w-0">
                <h2 class="text-2xl font-bold text-navy">{{ __('pages.exams.faq_title') }}</h2>
                <p class="mt-2 text-navy-muted">{{ __('pages.exams.faq_subtitle') }}</p>
                <ul class="mt-6 space-y-3 text-sm">
                    <li><a href="{{ route('faq') }}" class="font-semibold text-accent hover:text-accent-dark">{{ __('pages.exams.faq_official') }}</a></li>
                    <li><a href="{{ route('faq') }}" class="font-semibold text-accent hover:text-accent-dark">{{ __('pages.exams.faq_practice') }}</a></li>
                    <li><a href="{{ route('faq') }}" class="font-semibold text-accent hover:text-accent-dark">{{ __('pages.exams.faq_languages') }}</a></li>
                </ul>
            </div>
            <div class="rounded-3xl border border-border bg-white p-6 shadow-soft">
                <h2 class="text-lg font-semibold text-navy">{{ __('pages.exams.notice_title') }}</h2>
                <p class="mt-3 text-sm leading-relaxed text-navy-muted">
                    {{ __('pages.exams.notice_body') }}
                </p>
                <div class="mt-5">
                    <x-ui.disclaimer-notice />
                </div>
            </div>
        </div>
    </section>

    <x-ui.ad-slot name="content-bottom" class="section-shell py-4" />
</x-layouts.site>
