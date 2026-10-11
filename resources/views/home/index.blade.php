<x-layouts.site
    :title="config('menkyo.name').' ('.config('menkyo.name_ja').') | '.__('home.title_suffix')"
    :description="__('home.meta_description')"
    :canonical="url('/')"
    :json-ld="$jsonLd ?? null"
>
    {{-- A. Hero --}}
    <section class="relative overflow-hidden border-b border-border gradient-hero" data-parallax-layer>
        <div class="seigaiha-layer" aria-hidden="true"></div>
        <div class="section-shell relative py-8 sm:py-11 lg:py-14">
            <div class="grid items-center gap-8 lg:grid-cols-2 lg:gap-12">
                <div class="max-w-xl min-w-0">
                    <p class="hero-animate hero-animate-delay-1 inline-flex max-w-full flex-wrap items-center gap-2 rounded-full border border-accent/20 bg-paper/90 px-3 py-1 text-sm font-semibold text-accent shadow-soft">
                        <span>{{ config('menkyo.name_ja') }}</span>
                        <span class="text-navy-muted">&middot;</span>
                        <span class="font-medium text-navy-muted">{{ config('menkyo.name') }}</span>
                    </p>
                    <h1 class="hero-title-mask mt-4 text-3xl font-bold tracking-tight text-navy sm:mt-5 sm:text-4xl lg:text-display">
                        <span class="hero-title-inner">{{ __('home.hero_title') }}</span>
                    </h1>
                    <div class="hero-accent-line" aria-hidden="true"></div>
                    <p class="hero-animate hero-animate-delay-3 mt-3 text-base leading-relaxed text-navy-muted sm:mt-4 sm:text-lg">
                        {{ __('home.hero_lead') }}
                    </p>
                    <div class="hero-animate hero-animate-delay-4 mt-6 flex flex-col gap-3 sm:mt-8 sm:flex-row sm:items-center">
                        <a href="{{ route('exams.index') }}" class="btn-primary w-full sm:w-auto">
                            {{ __('home.start_free_practice') }}
                        </a>
                        <a href="{{ route('learn.index') }}" class="btn-gold w-full sm:w-auto">
                            {{ __('home.learn_cta') }}
                        </a>
                    </div>
                    <p class="hero-animate hero-animate-delay-5 mt-4 text-xs leading-relaxed text-navy-muted sm:mt-5 sm:text-sm">
                        {{ __('home.independent_note') }}
                    </p>
                </div>

                <x-ui.hero-visual class="mt-2 lg:mt-0 lg:justify-self-end" />
            </div>
        </div>
    </section>

    {{-- B. Exam selection --}}
    <section id="exam-modes" class="section-pad section-paper">
        <div class="section-shell">
            <div class="motion-reveal-mask" data-reveal>
                <x-ui.section-heading
                    :title="__('home.choose_path_title')"
                    :subtitle="__('home.choose_path_subtitle')"
                />
            </div>
            <div class="motion-stagger-group mt-8 grid gap-5 md:grid-cols-3 md:gap-6" data-reveal>
                <div class="motion-reveal-scale" data-reveal-child>
                    <x-ui.exam-mode-card
                        :title="__('home.gaimen_title')"
                        :subtitle="__('home.gaimen_subtitle')"
                        :japanese="config('menkyo.exam_labels.gaimen')"
                        :description="__('home.gaimen_description')"
                        :href="route('exams.gaimen-kirikae')"
                        icon="gaimen"
                        accent="teal"
                        :cta="__('home.gaimen_cta')"
                    />
                </div>
                <div class="motion-reveal-scale" data-reveal-child>
                    <x-ui.exam-mode-card
                        :title="__('home.karimen_title')"
                        :subtitle="__('home.karimen_subtitle')"
                        :japanese="config('menkyo.exam_labels.karimen')"
                        :description="__('home.karimen_description')"
                        :href="route('exams.karimen')"
                        icon="karimen"
                        accent="amber"
                        :cta="__('home.karimen_cta')"
                    />
                </div>
                <div class="motion-reveal-scale" data-reveal-child>
                    <x-ui.exam-mode-card
                        :title="__('home.honmen_title')"
                        :subtitle="__('home.honmen_subtitle')"
                        :japanese="config('menkyo.exam_labels.honmen')"
                        :description="__('home.honmen_description')"
                        :href="route('exams.honmen')"
                        icon="honmen"
                        accent="blue"
                        :cta="__('home.honmen_cta')"
                    />
                </div>
            </div>
        </div>
    </section>

    {{-- C. Learning journey --}}
    <section class="border-y border-border section-washi section-pad">
        <div class="section-shell">
            <div class="motion-reveal-mask" data-reveal>
                <x-ui.section-heading
                    :title="__('home.how_it_works_title')"
                    :subtitle="__('home.how_it_works_subtitle')"
                />
            </div>
            <ol class="journey-track mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4 sm:gap-5" data-reveal>
                @foreach ([
                    ['step' => '01', 'title' => __('home.step1_title'), 'body' => __('home.step1_body'), 'href' => route('learn.index')],
                    ['step' => '02', 'title' => __('home.step2_title'), 'body' => __('home.step2_body'), 'href' => route('exams.index')],
                    ['step' => '03', 'title' => __('home.step3_title'), 'body' => __('home.step3_body'), 'href' => route('exams.index')],
                    ['step' => '04', 'title' => __('home.step4_title'), 'body' => __('home.step4_body'), 'href' => route('learn.study-methods')],
                ] as $index => $item)
                    <li class="motion-reveal-up motion-delay-{{ min($index + 1, 4) }} relative" data-reveal>
                        <a href="{{ $item['href'] }}" class="block h-full rounded-3xl border border-accent/20 bg-gradient-to-br from-accent/5 via-paper to-paper p-5 shadow-soft transition hover:border-accent/45 hover:shadow-lift sm:p-6">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-accent-bright to-accent text-sm font-bold text-white shadow-soft">{{ $item['step'] }}</span>
                            <h3 class="mt-3 text-lg font-semibold text-navy">{{ $item['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-navy-muted">{{ $item['body'] }}</p>
                        </a>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- D. Single-language sample question --}}
    <section class="section-pad section-paper">
        <div class="section-shell">
            <div class="grid items-start gap-8 lg:grid-cols-12 lg:gap-10">
                <div class="motion-reveal-left min-w-0 lg:col-span-4" data-reveal>
                    <x-ui.section-heading
                        :title="__('home.sample_section_title')"
                        :subtitle="__('home.sample_section_subtitle')"
                    />
                </div>
                <div class="motion-reveal-right min-w-0 lg:col-span-8" data-reveal>
                    <x-ui.sample-question-preview />
                </div>
            </div>
        </div>
    </section>

    <x-ui.ad-slot name="content-mid" class="section-shell py-2" />

    {{-- Learn feature --}}
    <section class="section-pad section-paper" data-home-learn-feature>
        <div class="section-shell grid items-center gap-8 lg:grid-cols-2 lg:gap-12">
            <div class="motion-reveal-left min-w-0" data-reveal>
                <x-ui.section-heading
                    :title="__('home.learn_feature_title')"
                    :subtitle="__('home.learn_feature_body')"
                />
                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('learn.index') }}" class="btn-primary w-full sm:w-auto">{{ __('home.learn_feature_cta') }}</a>
                    <a href="{{ route('learn.study-methods') }}" class="btn-secondary w-full sm:w-auto">{{ __('pages.learn.study_methods_cta') }}</a>
                </div>
            </div>
            <figure class="motion-reveal-right overflow-hidden rounded-3xl border border-border bg-paper shadow-lift" data-reveal>
                <x-ui.human-image
                    image-key="learner_studying_desk"
                    :alt="__('home.learn_feature_alt')"
                    sizes="(max-width: 1024px) 100vw, 560px"
                />
            </figure>
        </div>
    </section>

    {{-- E. Road signs --}}
    <section class="border-y border-border section-washi section-pad">
        <div class="section-shell">
            <div class="grid items-center gap-8 lg:grid-cols-2 lg:gap-12">
                <div class="motion-reveal-left min-w-0" data-reveal>
                    <x-ui.section-heading
                        :title="__('home.signs_section_title')"
                        :subtitle="__('home.signs_section_subtitle')"
                    />
                    <a href="{{ route('learn.road-signs') }}" class="btn-vermilion mt-6 w-full sm:w-auto">
                        {{ __('home.signs_cta') }}
                    </a>
                </div>
                <div class="motion-stagger-group grid grid-cols-2 gap-3 sm:gap-4" data-reveal>
                    @foreach ([
                        ['label' => __('home.sign_regulatory'), 'hint' => __('home.sign_regulatory_hint'), 'shape' => 'octagon'],
                        ['label' => __('home.sign_warning'), 'hint' => __('home.sign_warning_hint'), 'shape' => 'diamond'],
                        ['label' => __('home.sign_guidance'), 'hint' => __('home.sign_guidance_hint'), 'shape' => 'rect'],
                        ['label' => __('home.sign_supplementary'), 'hint' => __('home.sign_supplementary_hint'), 'shape' => 'plate'],
                    ] as $signGroup)
                        <article class="motion-reveal-scale min-w-0 rounded-2xl border border-border bg-paper p-4 shadow-soft transition duration-300 hover:-translate-y-1 hover:shadow-lift motion-reduce:transform-none sm:p-5" data-reveal-child>
                            <div class="flex h-12 w-12 items-center justify-center transition duration-300 group-hover:scale-105" aria-hidden="true">
                                @if ($signGroup['shape'] === 'octagon')
                                    <svg viewBox="0 0 40 40" class="h-10 w-10" role="img"><polygon points="12,2 28,2 38,12 38,28 28,38 12,38 2,28 2,12" fill="#C6533E" stroke="#172D46" stroke-width="1.5"/></svg>
                                @elseif ($signGroup['shape'] === 'diamond')
                                    <svg viewBox="0 0 40 40" class="h-10 w-10" role="img"><polygon points="20,3 37,20 20,37 3,20" fill="#B88A44" stroke="#172D46" stroke-width="1.5"/></svg>
                                @elseif ($signGroup['shape'] === 'rect')
                                    <svg viewBox="0 0 40 40" class="h-10 w-10" role="img"><rect x="4" y="10" width="32" height="20" rx="2" fill="#26466D" stroke="#172D46" stroke-width="1.5"/></svg>
                                @else
                                    <svg viewBox="0 0 40 40" class="h-10 w-10" role="img"><rect x="6" y="14" width="28" height="12" rx="1" fill="#FFFEFA" stroke="#172D46" stroke-width="1.5"/></svg>
                                @endif
                            </div>
                            <h3 class="mt-3 font-semibold text-navy break-words">{{ $signGroup['label'] }}</h3>
                            <p class="mt-1 text-sm text-navy-muted break-words">{{ $signGroup['hint'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- F. Resources --}}
    <section class="section-pad section-paper">
        <div class="section-shell">
            <div class="motion-reveal-mask" data-reveal>
                <x-ui.section-heading
                    :title="__('home.resources_title')"
                    :subtitle="__('home.resources_subtitle')"
                />
            </div>
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="motion-reveal-left" data-reveal>
                    <x-ui.resource-card
                        :title="__('home.resource_pedestrian')"
                        :description="__('home.resource_pedestrian_desc')"
                        :href="route('learn.traffic-rules.pedestrian-priority')"
                        icon="book"
                        accent="matcha"
                    />
                </div>
                <div class="motion-reveal-up" data-reveal>
                    <x-ui.resource-card
                        :title="__('home.resource_traffic_rules')"
                        :description="__('home.resource_traffic_rules_desc')"
                        :href="route('learn.traffic-rules')"
                        icon="book"
                        accent="blue"
                    />
                </div>
                <div class="motion-reveal-up motion-delay-1" data-reveal>
                    <x-ui.resource-card
                        :title="__('home.resource_road_signs')"
                        :description="__('home.resource_road_signs_desc')"
                        :href="route('learn.road-signs')"
                        icon="sign"
                        accent="vermilion"
                    />
                </div>
                <div class="motion-reveal-right" data-reveal>
                    <x-ui.resource-card
                        :title="__('home.resource_foreign_license')"
                        :description="__('home.resource_foreign_license_desc')"
                        :href="route('guides.foreign-license-conversion')"
                        icon="globe"
                        accent="gold"
                    />
                </div>
                <div class="motion-reveal-right" data-reveal>
                    <x-ui.resource-card
                        :title="__('home.resource_documents')"
                        :description="__('home.resource_documents_desc')"
                        :href="route('guides.required-documents')"
                        icon="docs"
                        accent="navy"
                    />
                </div>
                <div class="motion-reveal-up motion-delay-2" data-reveal>
                    <x-ui.resource-card
                        :title="__('home.resource_centers')"
                        :description="__('home.resource_centers_desc')"
                        :href="route('guides.exam-centers')"
                        icon="map"
                        accent="blue"
                    />
                </div>
                <div class="motion-reveal-left" data-reveal>
                    <x-ui.resource-card
                        :title="__('home.resource_hazard')"
                        :description="__('home.resource_hazard_desc')"
                        :href="route('learn.hazard-prediction')"
                        icon="alert"
                        accent="warm"
                    />
                </div>
            </div>
        </div>
    </section>

    {{-- G. FAQ --}}
    <section class="border-t border-border section-washi section-pad">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="motion-reveal-mask" data-reveal>
                <x-ui.section-heading
                    :title="__('home.faq_section_title')"
                    :subtitle="__('home.faq_section_subtitle')"
                />
            </div>
            <div class="motion-reveal-up motion-delay-2 mt-8 rounded-3xl border border-border bg-paper/80 p-2 shadow-soft sm:p-3" data-reveal>
                <livewire:faq-accordion />
            </div>
            <p class="mt-6 text-center">
                <a href="{{ route('faq') }}" class="font-semibold text-accent transition hover:text-accent-dark">
                    {{ __('home.faq_view_all') }}
                </a>
            </p>
        </div>
    </section>

    <x-ui.ad-slot name="content-bottom" class="section-shell py-2" />

    {{-- Final CTA --}}
    <section class="motion-reveal-scale border-t border-border section-indigo py-12 sm:py-14" data-reveal>
        <div class="section-shell text-center">
            <h2 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">{{ __('home.cta_title') }}</h2>
            <p class="mx-auto mt-3 max-w-2xl text-base text-white/80">
                {{ __('home.cta_body') }}
            </p>
            <div class="mt-8 flex flex-col items-stretch justify-center gap-3 sm:flex-row sm:items-center">
                <a href="{{ route('exams.index') }}" class="inline-flex min-h-12 w-full items-center justify-center rounded-2xl border border-gold/40 bg-gradient-to-b from-gold-bright to-gold px-6 py-3 text-base font-semibold text-navy shadow-soft transition hover:shadow-lift focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white sm:w-auto">
                    {{ __('menkyo.cta.start_free_practice') }}
                </a>
                <a
                    href="{{ route('register') }}"
                    class="inline-flex min-h-12 w-full items-center justify-center rounded-2xl border border-white/35 px-6 py-3 text-base font-semibold text-white transition hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white sm:w-auto"
                >
                    {{ __('home.cta_create_account') }}
                </a>
            </div>
        </div>
    </section>
</x-layouts.site>
