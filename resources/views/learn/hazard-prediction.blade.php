<x-layouts.site
    :title="__('pages.hazard.meta_title')"
    :description="__('pages.hazard.meta_description')"
    :canonical="url()->current()"
>
    <x-ui.breadcrumb :items="[
        ['label' => __('pages.home'), 'url' => route('home')],
        ['label' => __('pages.learn.breadcrumb'), 'url' => route('learn.index')],
        ['label' => __('pages.hazard.breadcrumb'), 'url' => route('learn.hazard-prediction')],
    ]" />

    <x-ui.page-hero
        :title="__('pages.hazard.title')"
        :subtitle="__('pages.hazard.subtitle')"
    />

    <x-layouts.learning :sidebar-label="__('pages.learn.breadcrumb')">
        <p class="text-base leading-relaxed text-navy-muted sm:text-lg">{{ __('pages.hazard.intro') }}</p>

        <div class="space-y-5">
            @foreach ([1, 2, 3, 4] as $section)
                <section class="rounded-2xl border border-border bg-white p-5 shadow-soft sm:p-6">
                    <h2 class="text-xl font-semibold text-navy break-words">{{ __('pages.hazard.s'.$section.'_title') }}</h2>
                    <p class="mt-3 text-sm leading-relaxed text-navy-muted break-words">{{ __('pages.hazard.s'.$section.'_body') }}</p>
                </section>
            @endforeach
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
            <a href="{{ route('learn.traffic-rules') }}" class="btn-secondary inline-flex min-h-11 w-full items-center justify-center sm:w-auto">
                {{ __('pages.hazard.cta_rules') }}
            </a>
            <a href="{{ route('exams.index') }}" class="btn-primary inline-flex min-h-11 w-full items-center justify-center sm:w-auto">
                {{ __('pages.hazard.cta_exams') }}
            </a>
        </div>

        <x-slot:sidebar>
            <p class="text-sm font-semibold uppercase tracking-wide text-navy-muted">{{ __('pages.learn.breadcrumb') }}</p>
            <x-ui.resource-card
                :title="__('pages.learn.card_rules_title')"
                :description="__('pages.learn.card_rules_desc')"
                :href="route('learn.traffic-rules')"
                icon="book"
                accent="blue"
                :equal-height="false"
            />
            <x-ui.resource-card
                :title="__('pages.learn.card_signs_title')"
                :description="__('pages.learn.card_signs_desc')"
                :href="route('learn.road-signs')"
                icon="sign"
                accent="amber"
                :equal-height="false"
            />
        </x-slot:sidebar>

        <x-slot:below>
            <x-ui.disclaimer-notice class="p-4 sm:p-5" />
        </x-slot:below>
    </x-layouts.learning>
</x-layouts.site>
