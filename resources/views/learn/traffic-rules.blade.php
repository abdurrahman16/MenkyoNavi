<x-layouts.site
    :title="__('pages.traffic_rules.meta_title')"
    :description="__('pages.traffic_rules.meta_description')"
    :canonical="url()->current()"
>
    <x-ui.breadcrumb :items="[
        ['label' => __('pages.home'), 'url' => route('home')],
        ['label' => __('pages.learn.breadcrumb'), 'url' => route('learn.index')],
        ['label' => __('pages.traffic_rules.breadcrumb'), 'url' => route('learn.traffic-rules')],
    ]" />

    <x-ui.page-hero
        :title="__('pages.traffic_rules.title')"
        :subtitle="__('pages.traffic_rules.subtitle')"
    />

    <x-layouts.learning :sidebar-label="__('pages.traffic_rules.nav_label')">
        <p class="text-base leading-relaxed text-navy-muted sm:text-lg">{{ __('pages.traffic_rules.intro') }}</p>

        <div class="grid gap-5 lg:grid-cols-2" data-featured-guides>
            <section class="rounded-3xl border border-accent/25 bg-gradient-to-br from-washi via-paper to-surface p-5 shadow-lift sm:p-6" data-featured-guide>
                <img
                    src="{{ asset(config('content.pedestrian_priority.thumbnail')) }}"
                    alt=""
                    width="640"
                    height="320"
                    class="mb-4 h-36 w-full rounded-2xl bg-washi object-contain"
                    loading="lazy"
                />
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-accent">{{ __('pages.traffic_rules.featured_label') }}</p>
                <h2 class="mt-2 text-xl font-bold text-navy sm:text-2xl">{{ __('pages.traffic_rules.featured_title') }}</h2>
                <p class="mt-3 text-sm leading-relaxed text-navy-muted sm:text-base">{{ __('pages.traffic_rules.featured_body') }}</p>
                <a href="{{ route('learn.traffic-rules.pedestrian-priority') }}" class="btn-primary mt-5 inline-flex min-h-11 items-center justify-center">
                    {{ __('pages.traffic_rules.featured_cta') }}
                </a>
            </section>
            <section class="rounded-3xl border border-navy/20 bg-gradient-to-br from-paper via-washi to-surface p-5 shadow-lift sm:p-6" data-featured-guide-intersection>
                <img
                    src="{{ asset(config('content.intersection_priority.thumbnail')) }}"
                    alt=""
                    width="960"
                    height="480"
                    class="mb-4 h-36 w-full rounded-2xl bg-washi object-contain"
                    loading="lazy"
                />
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-accent">{{ __('pages.traffic_rules.featured2_label') }}</p>
                <h2 class="mt-2 text-xl font-bold text-navy sm:text-2xl">{{ __('pages.traffic_rules.featured2_title') }}</h2>
                <p class="mt-3 text-sm leading-relaxed text-navy-muted sm:text-base">{{ __('pages.traffic_rules.featured2_body') }}</p>
                <a href="{{ route('learn.traffic-rules.intersection-priority') }}" class="btn-primary mt-5 inline-flex min-h-11 items-center justify-center">
                    {{ __('pages.traffic_rules.featured2_cta') }}
                </a>
            </section>
        </div>

        <div class="space-y-5">
            @foreach ([1, 2, 3, 4, 5] as $section)
                <section class="rounded-2xl border border-border bg-white p-5 shadow-soft sm:p-6">
                    <h2 class="text-xl font-semibold text-navy break-words">{{ __('pages.traffic_rules.s'.$section.'_title') }}</h2>
                    <p class="mt-3 text-sm leading-relaxed text-navy-muted break-words">{{ __('pages.traffic_rules.s'.$section.'_body') }}</p>
                </section>
            @endforeach
        </div>

        <section class="rounded-2xl border border-border bg-white p-5 shadow-soft sm:p-6">
            <h2 class="text-xl font-semibold text-navy break-words">{{ __('pages.traffic_rules.topics_title') }}</h2>
            <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach ([1, 2, 3, 4, 5, 6, 7, 8] as $topic)
                    <li class="rounded-lg bg-surface px-3 py-2.5 text-sm leading-snug text-navy-muted break-words">{{ __('pages.traffic_rules.topic_'.$topic) }}</li>
                @endforeach
            </ul>
        </section>

        <p class="rounded-xl border border-border bg-surface/70 px-4 py-3 text-sm leading-relaxed text-navy-muted break-words">
            {{ __('pages.traffic_rules.empty_body') }}
        </p>

        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
            <a href="{{ route('learn.road-signs') }}" class="btn-secondary inline-flex min-h-11 w-full items-center justify-center sm:w-auto">
                {{ __('pages.traffic_rules.cta_signs') }}
            </a>
            <a href="{{ route('exams.karimen') }}" class="btn-primary inline-flex min-h-11 w-full items-center justify-center sm:w-auto">
                {{ __('pages.traffic_rules.cta_practice') }}
            </a>
        </div>

        <x-slot:sidebar>
            <p class="text-sm font-semibold uppercase tracking-wide text-navy-muted">{{ __('pages.traffic_rules.nav_label') }}</p>
            <x-ui.resource-card
                :title="__('pages.traffic_rules.link_signs')"
                :description="__('pages.traffic_rules.link_signs_desc')"
                :href="route('learn.road-signs')"
                icon="sign"
                accent="amber"
                :equal-height="false"
            />
            <x-ui.resource-card
                :title="__('pages.traffic_rules.link_hazard')"
                :description="__('pages.traffic_rules.link_hazard_desc')"
                :href="route('learn.hazard-prediction')"
                icon="alert"
                accent="teal"
                :equal-height="false"
            />
            <x-ui.resource-card
                :title="__('pages.traffic_rules.link_karimen')"
                :description="__('pages.traffic_rules.link_karimen_desc')"
                :href="route('exams.karimen')"
                icon="book"
                accent="blue"
                :equal-height="false"
            />
        </x-slot:sidebar>

        <x-slot:below>
            <x-ui.disclaimer-notice class="p-4 sm:p-5" />
        </x-slot:below>
    </x-layouts.learning>
</x-layouts.site>
