<x-layouts.site
    :title="__('pages.learn.meta_title')"
    :description="__('pages.learn.meta_description')"
    :canonical="url()->current()"
>
    <x-ui.breadcrumb :items="[
        ['label' => __('pages.home'), 'url' => route('home')],
        ['label' => __('pages.learn.breadcrumb'), 'url' => route('learn.index')],
    ]" />

    <section class="relative overflow-hidden border-b border-border gradient-hero py-10 sm:py-12" data-learn-hero>
        <div class="section-shell relative grid items-center gap-8 lg:grid-cols-12 lg:gap-10">
            <div class="min-w-0 lg:col-span-7">
                <p class="section-label">{{ __('pages.learn.breadcrumb') }}</p>
                <h1 class="mt-2 text-balance text-3xl font-bold tracking-tight text-navy sm:text-4xl">
                    {{ __('pages.learn.title') }}
                </h1>
                <div class="motion-divider" aria-hidden="true"></div>
                <p class="mt-4 max-w-2xl text-base leading-relaxed text-navy-muted sm:text-lg">
                    {{ __('pages.learn.subtitle') }}
                </p>
                <p class="mt-4 max-w-2xl text-sm leading-relaxed text-navy-muted sm:text-base">
                    {{ __('pages.learn.intro') }}
                </p>
                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <a href="{{ route('learn.study-methods') }}" class="btn-primary w-full sm:w-auto">{{ __('pages.learn.study_methods_cta') }}</a>
                    <a href="{{ route('exams.index') }}" class="btn-gold w-full sm:w-auto">{{ __('pages.learn.practice_cta') }}</a>
                </div>
            </div>
            <div class="motion-reveal-scale min-w-0 lg:col-span-5" data-reveal>
                <figure class="overflow-hidden rounded-3xl border border-border bg-paper shadow-lift">
                    <x-ui.human-image
                        image-key="learner_studying_desk"
                        :alt="__('pages.learn.hero_image_alt')"
                        :priority="true"
                        sizes="(max-width: 1024px) 100vw, 480px"
                    />
                    <figcaption class="border-t border-border px-4 py-3 text-sm text-navy-muted">
                        {{ __('pages.learn.hero_image_caption') }}
                    </figcaption>
                </figure>
            </div>
        </div>
    </section>

    <section class="border-b border-border section-washi section-pad" data-learner-journey>
        <div class="section-shell">
            <x-ui.section-heading
                :title="__('pages.learn.journey_title')"
                :subtitle="__('pages.learn.journey_subtitle')"
            />
            <ol class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ([
                    ['n' => '01', 'label' => __('pages.learn.journey_step_learn'), 'href' => route('learn.index')],
                    ['n' => '02', 'label' => __('pages.learn.journey_step_practice'), 'href' => route('exams.index')],
                    ['n' => '03', 'label' => __('pages.learn.journey_step_review'), 'href' => route('exams.index')],
                    ['n' => '04', 'label' => __('pages.learn.journey_step_improve'), 'href' => route('learn.study-methods')],
                    ['n' => '05', 'label' => __('pages.learn.journey_step_repeat'), 'href' => route('exams.index')],
                ] as $step)
                    <li>
                        <a href="{{ $step['href'] }}" class="flex h-full flex-col rounded-3xl border border-accent/20 bg-gradient-to-br from-accent/5 via-paper to-paper p-5 shadow-soft transition hover:border-accent/45 hover:shadow-lift">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-accent-bright to-accent text-sm font-bold text-white">{{ $step['n'] }}</span>
                            <span class="mt-3 text-sm font-semibold leading-snug text-navy">{{ $step['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section class="section-pad section-paper" data-study-roadmap>
        <div class="section-shell">
            <x-ui.section-heading
                :title="__('pages.learn.roadmap_title')"
                :subtitle="__('pages.learn.roadmap_subtitle')"
            />
            <div class="mt-8 space-y-4">
                @foreach ([
                    [
                        'stage' => '1',
                        'title' => __('pages.learn.roadmap_stage1_title'),
                        'body' => __('pages.learn.roadmap_stage1_body'),
                        'accent' => 'matcha',
                        'links' => [
                            ['label' => __('pages.learn.card_rules_title'), 'url' => route('learn.traffic-rules')],
                            ['label' => __('pages.learn.featured_title'), 'url' => route('learn.traffic-rules.pedestrian-priority')],
                            ['label' => __('pages.traffic_rules.featured2_title'), 'url' => route('learn.traffic-rules.intersection-priority')],
                        ],
                    ],
                    [
                        'stage' => '2',
                        'title' => __('pages.learn.roadmap_stage2_title'),
                        'body' => __('pages.learn.roadmap_stage2_body'),
                        'accent' => 'vermilion',
                        'links' => [
                            ['label' => __('pages.learn.card_signs_title'), 'url' => route('learn.road-signs')],
                            ['label' => __('pages.learn.roadmap_stage2_practice'), 'url' => route('learn.road-signs').'#practice'],
                        ],
                    ],
                    [
                        'stage' => '3',
                        'title' => __('pages.learn.roadmap_stage3_title'),
                        'body' => __('pages.learn.roadmap_stage3_body'),
                        'accent' => 'indigo',
                        'links' => [
                            ['label' => config('menkyo.exam_labels.karimen'), 'url' => route('exams.karimen')],
                            ['label' => config('menkyo.exam_labels.honmen'), 'url' => route('exams.honmen')],
                            ['label' => config('menkyo.exam_labels.gaimen'), 'url' => route('exams.gaimen-kirikae')],
                        ],
                    ],
                    [
                        'stage' => '4',
                        'title' => __('pages.learn.roadmap_stage4_title'),
                        'body' => __('pages.learn.roadmap_stage4_body'),
                        'accent' => 'gold',
                        'links' => [
                            ['label' => __('pages.learn.roadmap_stage4_results'), 'url' => route('exams.attempts.index')],
                            ['label' => __('panel.mistake_vault'), 'url' => route('mistakes.index')],
                        ],
                    ],
                    [
                        'stage' => '5',
                        'title' => __('pages.learn.roadmap_stage5_title'),
                        'body' => __('pages.learn.roadmap_stage5_body'),
                        'accent' => 'warm',
                        'links' => [
                            ['label' => __('pages.learn.study_methods_cta'), 'url' => route('learn.study-methods')],
                            ['label' => __('pages.learn.practice_cta'), 'url' => route('exams.index')],
                        ],
                    ],
                ] as $stage)
                    <article class="learn-card--{{ $stage['accent'] }} rounded-3xl border p-5 shadow-soft sm:p-6" data-roadmap-stage="{{ $stage['stage'] }}">
                        <div class="flex flex-wrap items-start gap-4">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-navy text-sm font-bold text-white">{{ $stage['stage'] }}</span>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-lg font-bold text-navy sm:text-xl">{{ $stage['title'] }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-navy-muted sm:text-base">{{ $stage['body'] }}</p>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    @foreach ($stage['links'] as $link)
                                        <a href="{{ $link['url'] }}" class="inline-flex min-h-10 items-center rounded-xl border border-border bg-paper px-3 py-2 text-sm font-semibold text-accent transition hover:border-accent/40 hover:text-accent-dark">
                                            {{ $link['label'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="border-y border-border section-washi section-pad">
        <div class="section-shell">
            <x-ui.section-heading
                :title="__('pages.learn.topics_title')"
                :subtitle="__('pages.learn.topics_subtitle')"
            />
            <div class="mt-8 grid items-stretch gap-6 md:grid-cols-3">
                <a href="{{ route('learn.road-signs') }}" class="learn-card--vermilion group flex h-full flex-col overflow-hidden rounded-3xl border shadow-soft transition hover:shadow-lift">
                    <x-ui.human-image image-key="learner_road_signs" :alt="__('pages.learn.topic_signs_alt')" sizes="(max-width: 768px) 100vw, 33vw" class="aspect-[16/10]" />
                    <div class="flex flex-1 flex-col p-5">
                        <h2 class="text-lg font-bold text-navy">{{ __('pages.learn.card_signs_title') }}</h2>
                        <p class="mt-2 flex-1 text-sm leading-relaxed text-navy-muted">{{ __('pages.learn.card_signs_desc') }}</p>
                    </div>
                </a>
                <a href="{{ route('learn.traffic-rules') }}" class="learn-card--indigo group flex h-full flex-col overflow-hidden rounded-3xl border shadow-soft transition hover:shadow-lift">
                    <x-ui.human-image image-key="driver_intersection_observation" :alt="__('pages.learn.topic_rules_alt')" sizes="(max-width: 768px) 100vw, 33vw" class="aspect-[16/10]" />
                    <div class="flex flex-1 flex-col p-5">
                        <h2 class="text-lg font-bold text-navy">{{ __('pages.learn.card_rules_title') }}</h2>
                        <p class="mt-2 flex-1 text-sm leading-relaxed text-navy-muted">{{ __('pages.learn.card_rules_desc') }}</p>
                    </div>
                </a>
                <a href="{{ route('learn.hazard-prediction') }}" class="learn-card--gold group flex h-full flex-col overflow-hidden rounded-3xl border shadow-soft transition hover:shadow-lift">
                    <x-ui.human-image image-key="focused_study_session" :alt="__('pages.learn.topic_hazard_alt')" sizes="(max-width: 768px) 100vw, 33vw" class="aspect-[16/10]" />
                    <div class="flex flex-1 flex-col p-5">
                        <h2 class="text-lg font-bold text-navy">{{ __('pages.learn.card_hazard_title') }}</h2>
                        <p class="mt-2 flex-1 text-sm leading-relaxed text-navy-muted">{{ __('pages.learn.card_hazard_desc') }}</p>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <section class="section-pad section-paper">
        <div class="section-shell space-y-6">
            <section class="learn-card--matcha rounded-3xl border border-matcha/30 p-6 shadow-soft sm:p-8" data-featured-guide>
                <div class="grid items-center gap-6 lg:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-matcha">{{ __('pages.learn.featured_label') }}</p>
                        <h2 class="mt-2 text-2xl font-bold text-navy">{{ __('pages.learn.featured_title') }}</h2>
                        <p class="mt-3 max-w-3xl text-sm leading-relaxed text-navy-muted sm:text-base">{{ __('pages.learn.featured_body') }}</p>
                        <a href="{{ route('learn.traffic-rules.pedestrian-priority') }}" class="btn-matcha mt-5 inline-flex min-h-11 items-center justify-center">
                            {{ __('pages.learn.featured_cta') }}
                        </a>
                    </div>
                    <figure class="overflow-hidden rounded-3xl border border-border bg-paper">
                        <x-ui.human-image image-key="pedestrians_crosswalk" :alt="__('pages.learn.featured_image_alt')" sizes="(max-width: 1024px) 100vw, 480px" />
                    </figure>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-2" data-learn-featured-resources>
                <a href="{{ route('learn.study-methods') }}" class="learn-card--indigo rounded-3xl border p-5 shadow-soft transition hover:border-accent/50 hover:shadow-lift">
                    <x-ui.human-image image-key="focused_study_session" :alt="__('pages.learn.study_methods_image_alt')" sizes="(max-width: 768px) 100vw, 50vw" class="mb-3 aspect-[16/9] rounded-2xl" />
                    <h2 class="text-lg font-bold text-navy">{{ __('pages.learn.study_methods_title') }}</h2>
                    <p class="mt-2 text-sm leading-relaxed text-navy-muted">{{ __('pages.learn.study_methods_desc') }}</p>
                </a>
                <a href="{{ route('learn.traffic-rules.intersection-priority') }}" class="learn-card--gold rounded-3xl border p-5 shadow-soft transition hover:border-gold/50 hover:shadow-lift">
                    <x-ui.human-image image-key="driver_intersection_observation" :alt="__('pages.learn.intersection_image_alt')" sizes="(max-width: 768px) 100vw, 50vw" class="mb-3 aspect-[16/9] rounded-2xl" />
                    <h2 class="text-lg font-bold text-navy">{{ __('pages.traffic_rules.featured2_title') }}</h2>
                    <p class="mt-2 text-sm leading-relaxed text-navy-muted">{{ __('pages.traffic_rules.featured2_body') }}</p>
                </a>
            </section>

            <section class="rounded-3xl border border-border bg-white p-6 shadow-soft sm:p-8" data-study-checklist
                x-data="{
                    key: 'menkyo-learn-checklist-v1',
                    items: {},
                    init() {
                        try { this.items = JSON.parse(localStorage.getItem(this.key) || '{}') || {}; } catch (e) { this.items = {}; }
                    },
                    toggle(id) {
                        this.items[id] = ! this.items[id];
                        try { localStorage.setItem(this.key, JSON.stringify(this.items)); } catch (e) {}
                    }
                }"
            >
                <h2 class="text-xl font-bold text-navy">{{ __('pages.learn.checklist_title') }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-navy-muted" data-study-checklist-local-note>
                    {{ __('pages.learn.checklist_note') }}
                </p>
                <ul class="mt-5 space-y-3">
                    @foreach ([
                        ['id' => 'rules', 'label' => __('pages.learn.checklist_rules'), 'url' => route('learn.traffic-rules')],
                        ['id' => 'signs', 'label' => __('pages.learn.checklist_signs'), 'url' => route('learn.road-signs')],
                        ['id' => 'methods', 'label' => __('pages.learn.checklist_methods'), 'url' => route('learn.study-methods')],
                        ['id' => 'practice', 'label' => __('pages.learn.checklist_practice'), 'url' => route('exams.index')],
                        ['id' => 'review', 'label' => __('pages.learn.checklist_review'), 'url' => route('exams.index')],
                    ] as $item)
                        <li class="flex items-start gap-3 rounded-2xl border border-border/70 bg-washi/50 px-4 py-3">
                            <input type="checkbox" class="mt-1 h-4 w-4 rounded border-border text-accent focus:ring-accent" :checked="!!items['{{ $item['id'] }}']" @change="toggle('{{ $item['id'] }}')">
                            <a href="{{ $item['url'] }}" class="text-sm font-medium text-navy hover:text-accent">{{ $item['label'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="overflow-hidden rounded-3xl border border-accent/25 bg-gradient-to-br from-accent/10 via-paper to-washi p-6 shadow-soft sm:p-8" data-learn-practice-cta>
                <div class="grid items-center gap-6 lg:grid-cols-2">
                    <div>
                        <h2 class="text-2xl font-bold text-navy">{{ __('pages.learn.practice_banner_title') }}</h2>
                        <p class="mt-3 text-sm leading-relaxed text-navy-muted sm:text-base">{{ __('pages.learn.practice_banner_body') }}</p>
                        <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                            <a href="{{ route('exams.index') }}" class="btn-primary">{{ __('pages.learn.practice_cta') }}</a>
                            <a href="{{ route('learn.study-methods') }}" class="btn-secondary">{{ __('pages.learn.study_methods_cta') }}</a>
                        </div>
                    </div>
                    <figure class="overflow-hidden rounded-3xl border border-border bg-paper">
                        <x-ui.human-image image-key="exam_confidence_review" :alt="__('pages.learn.cta_image_alt')" sizes="(max-width: 1024px) 100vw, 480px" />
                    </figure>
                </div>
            </section>

            <x-ui.ad-slot name="content-bottom" class="mt-4" />

            <div class="mt-8">
                <x-ui.disclaimer-notice />
            </div>
        </div>
    </section>
</x-layouts.site>
