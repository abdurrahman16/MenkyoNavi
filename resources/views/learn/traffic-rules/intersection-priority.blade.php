@php
    use App\Support\Advertising;

    /** @var array<string, mixed> $content */
    /** @var array<string, mixed> $manifest */
    /** @var list<array<string, mixed>> $sources */
    $assets = $manifest['assets'] ?? [];
    $captions = $content['visuals']['captions'] ?? [];
    $alts = $content['visuals']['alts'] ?? [];
    $showMidAd = Advertising::shouldRenderSlot('content-mid');
    $showInlineAd = Advertising::shouldRenderSlot('article-inline');
    $showSidebarAd = Advertising::shouldRenderSlot('sidebar');
    $showBottomAd = Advertising::shouldRenderSlot('content-bottom');
    $toc = $content['toc'] ?? [];
    $routeName = 'learn.traffic-rules.intersection-priority';
@endphp

<x-layouts.site
    :title="$content['meta_title']"
    :description="$content['meta_description']"
    :canonical="route($routeName)"
    :robots="$robots"
    :json-ld="$jsonLd"
>
    <x-ui.breadcrumb :items="[
        ['label' => __('pages.home'), 'url' => route('home')],
        ['label' => __('pages.learn.breadcrumb'), 'url' => route('learn.index')],
        ['label' => __('pages.traffic_rules.breadcrumb'), 'url' => route('learn.traffic-rules')],
        ['label' => $content['breadcrumb'], 'url' => route($routeName)],
    ]" />

    <section class="relative overflow-hidden border-b border-border gradient-hero py-10 sm:py-12" data-guide-hero>
        <div class="section-shell relative grid items-center gap-8 lg:grid-cols-12 lg:gap-10">
            <div class="min-w-0 lg:col-span-6">
                <p class="section-label">{{ $content['hero_eyebrow'] }}</p>
                <h1 class="mt-2 text-balance text-3xl font-bold tracking-tight text-navy sm:text-4xl">{{ $content['title'] }}</h1>
                <div class="motion-divider" aria-hidden="true"></div>
                <p class="mt-4 max-w-2xl text-base leading-relaxed text-navy-muted sm:text-lg">{{ $content['subtitle'] }}</p>
                <div class="mt-4 flex flex-wrap gap-3 text-xs font-medium text-navy-muted sm:text-sm">
                    <span class="rounded-full border border-border bg-paper/80 px-3 py-1">{{ $content['reading_time'] }}</span>
                    <span class="rounded-full border border-border bg-paper/80 px-3 py-1">{{ $content['updated_label'] }}: {{ $manifest['updated_at'] ?? '' }}</span>
                </div>
            </div>
            <div class="motion-reveal-scale min-w-0 lg:col-span-6" data-reveal>
                <x-learn.guide-figure
                    asset-key="hero"
                    :assets="$assets"
                    :captions="$captions"
                    :alts="$alts"
                    :figure-label="$content['figure_label']"
                    :figure-number="1"
                    :priority="true"
                />
            </div>
        </div>
    </section>

    <div class="section-pad section-paper">
        <div class="section-shell grid items-start gap-8 lg:grid-cols-12 lg:gap-10">
            <article class="min-w-0 space-y-12 lg:col-span-8" data-educational-guide data-intersection-guide>
                @if (! empty($content['draft_notice']) || $usingFallback)
                    <p class="rounded-2xl border border-amber/40 bg-amber-soft px-4 py-3 text-sm leading-relaxed text-navy" role="status">
                        {{ $content['draft_notice'] ?: __('pages.traffic_rules.empty_body') }}
                    </p>
                @endif

                <p class="text-base leading-relaxed text-navy-muted sm:text-lg">{{ $content['intro'] }}</p>

                <nav class="rounded-3xl border border-navy/15 bg-gradient-to-br from-washi via-paper to-surface p-5 shadow-soft sm:p-6" aria-label="{{ $content['toc_label'] }}" data-guide-toc>
                    <h2 class="text-sm font-semibold uppercase tracking-[0.14em] text-accent">{{ $content['toc_label'] }}</h2>
                    <ol class="mt-4 grid gap-2 sm:grid-cols-2">
                        @foreach ($toc as $anchor => $label)
                            <li>
                                <a href="#{{ $anchor }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-navy transition hover:bg-white hover:text-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">
                                    {{ $label }}
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </nav>

                @if ($showMidAd)
                    <x-ui.ad-slot name="content-mid" class="mx-0" />
                @endif

                <section id="fundamentals" class="motion-reveal-up scroll-mt-24 space-y-5" data-reveal>
                    <h2 class="text-2xl font-bold text-navy">{{ $content['fundamentals']['title'] }}</h2>
                    <p class="font-medium leading-relaxed text-navy">{{ $content['fundamentals']['lead'] }}</p>
                    <p class="leading-relaxed text-navy-muted">{{ $content['fundamentals']['p1'] }}</p>
                    <p class="leading-relaxed text-navy-muted">{{ $content['fundamentals']['p2'] }}</p>
                    <x-learn.guide-figure asset-key="priority_compare" :assets="$assets" :captions="$captions" :alts="$alts" :figure-label="$content['figure_label']" :figure-number="2" />
                    <p class="leading-relaxed text-navy-muted">{{ $content['fundamentals']['p3'] }}</p>
                    <p class="leading-relaxed text-navy-muted">{{ $content['fundamentals']['p4'] }}</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($content['fundamentals']['cards'] ?? [] as $card)
                            <article class="rounded-3xl border border-border bg-white p-5 shadow-soft">
                                <h3 class="text-lg font-semibold text-navy">{{ $card['title'] }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-navy-muted">{{ $card['body'] }}</p>
                            </article>
                        @endforeach
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <aside class="rounded-2xl border border-navy/20 bg-navy/5 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-accent">{{ $content['legal_label'] }}</p>
                            <p class="mt-2 text-sm leading-relaxed text-navy">{{ $content['fundamentals']['law_note'] }}</p>
                        </aside>
                        <aside class="rounded-2xl border border-matcha/30 bg-matcha/10 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-matcha">{{ $content['tip_label'] }}</p>
                            <p class="mt-2 text-sm leading-relaxed text-navy">{{ $content['fundamentals']['recommend_note'] }}</p>
                        </aside>
                    </div>
                </section>

                <section id="stop_signs" class="motion-reveal-up scroll-mt-24 space-y-5" data-reveal>
                    <h2 class="text-2xl font-bold text-navy">{{ $content['stop_signs']['title'] }}</h2>
                    <p class="font-medium leading-relaxed text-navy">{{ $content['stop_signs']['lead'] }}</p>
                    <div class="grid items-start gap-5 lg:grid-cols-2">
                        <div class="space-y-4">
                            <p class="leading-relaxed text-navy-muted">{{ $content['stop_signs']['p1'] }}</p>
                            <p class="leading-relaxed text-navy-muted">{{ $content['stop_signs']['p2'] }}</p>
                            <p class="leading-relaxed text-navy-muted">{{ $content['stop_signs']['p3'] }}</p>
                        </div>
                        <x-learn.guide-figure asset-key="stop_sign" :assets="$assets" :captions="$captions" :alts="$alts" :figure-label="$content['figure_label']" :figure-number="3" />
                    </div>
                    <x-learn.guide-figure asset-key="stop_steps" :assets="$assets" :captions="$captions" :alts="$alts" :figure-label="$content['figure_label']" :figure-number="4" />
                    <div
                        class="rounded-3xl border border-border bg-white p-5 shadow-soft"
                        x-data="{ step: 1 }"
                        data-stop-steps
                    >
                        <h3 class="text-lg font-semibold text-navy">{{ $content['interaction_steps_title'] }}</h3>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($content['stop_signs']['steps'] ?? [] as $index => $step)
                                <button
                                    type="button"
                                    class="min-h-11 rounded-xl px-4 py-2 text-sm font-semibold transition"
                                    :class="step === {{ $index + 1 }} ? 'bg-navy text-white' : 'bg-surface text-navy'"
                                    @click="step = {{ $index + 1 }}"
                                >
                                    {{ $index + 1 }}
                                </button>
                            @endforeach
                        </div>
                        <ul class="mt-4 space-y-2">
                            @foreach ($content['stop_signs']['steps'] ?? [] as $index => $step)
                                <li
                                    class="rounded-2xl border border-border px-4 py-3 text-sm leading-relaxed text-navy"
                                    x-show="step === {{ $index + 1 }}"
                                    x-cloak
                                >
                                    {{ $step }}
                                </li>
                            @endforeach
                        </ul>
                        <noscript>
                            <ol class="mt-4 list-decimal space-y-2 pl-5 text-sm text-navy">
                                @foreach ($content['stop_signs']['steps'] ?? [] as $step)
                                    <li>{{ $step }}</li>
                                @endforeach
                            </ol>
                        </noscript>
                    </div>
                    <p class="rounded-2xl border-l-4 border-vermilion bg-vermilion-soft px-4 py-3 text-sm font-medium leading-relaxed text-navy">
                        {{ $content['stop_signs']['highlight'] }}
                    </p>
                </section>

                @if ($showInlineAd)
                    <x-ui.ad-slot name="article-inline" class="mx-0" />
                @endif

                <section id="signals" class="motion-reveal-up scroll-mt-24 space-y-5" data-reveal>
                    <h2 class="text-2xl font-bold text-navy">{{ $content['signals']['title'] }}</h2>
                    <p class="font-medium leading-relaxed text-navy">{{ $content['signals']['lead'] }}</p>
                    <x-learn.guide-figure asset-key="signals" :assets="$assets" :captions="$captions" :alts="$alts" :figure-label="$content['figure_label']" :figure-number="5" />
                    <p class="leading-relaxed text-navy-muted">{{ $content['signals']['p1'] }}</p>
                    <p class="leading-relaxed text-navy-muted">{{ $content['signals']['p2'] }}</p>
                    <p class="rounded-2xl border border-matcha/30 bg-matcha/10 px-4 py-3 text-sm leading-relaxed text-navy">{{ $content['signals']['tip'] }}</p>
                </section>

                <section id="turning" class="motion-reveal-up scroll-mt-24 space-y-5" data-reveal>
                    <h2 class="text-2xl font-bold text-navy">{{ $content['turning']['title'] }}</h2>
                    <p class="font-medium leading-relaxed text-navy">{{ $content['turning']['lead'] }}</p>
                    <div
                        class="rounded-3xl border border-border bg-white p-5 shadow-soft"
                        x-data="{ path: 'left' }"
                        data-path-reveal
                    >
                        <h3 class="text-lg font-semibold text-navy">{{ $content['interaction_path_title'] }}</h3>
                        <p class="mt-2 text-sm text-navy-muted">{{ $content['interaction_path_hint'] }}</p>
                        <div class="mt-4 flex flex-wrap gap-3">
                            <button type="button" class="btn-secondary min-h-11" @click="path = 'left'" :aria-pressed="(path === 'left').toString()">{{ $content['interaction_left'] }}</button>
                            <button type="button" class="btn-secondary min-h-11" @click="path = 'right'" :aria-pressed="(path === 'right').toString()">{{ $content['interaction_right'] }}</button>
                        </div>
                        <div class="mt-5 grid gap-5 lg:grid-cols-2">
                            <div x-show="path === 'left'" x-cloak>
                                <x-learn.guide-figure asset-key="left_turn" :assets="$assets" :captions="$captions" :alts="$alts" :figure-label="$content['figure_label']" :figure-number="6" />
                                <h4 class="mt-4 text-base font-semibold text-navy">{{ $content['turning']['left_title'] }}</h4>
                                <p class="mt-2 text-sm leading-relaxed text-navy-muted">{{ $content['turning']['left_body'] }}</p>
                            </div>
                            <div x-show="path === 'right'" x-cloak>
                                <x-learn.guide-figure asset-key="right_turn" :assets="$assets" :captions="$captions" :alts="$alts" :figure-label="$content['figure_label']" :figure-number="7" />
                                <h4 class="mt-4 text-base font-semibold text-navy">{{ $content['turning']['right_title'] }}</h4>
                                <p class="mt-2 text-sm leading-relaxed text-navy-muted">{{ $content['turning']['right_body'] }}</p>
                            </div>
                        </div>
                        <noscript>
                            <div class="mt-5 grid gap-5 lg:grid-cols-2">
                                <div>
                                    <x-learn.guide-figure asset-key="left_turn" :assets="$assets" :captions="$captions" :alts="$alts" />
                                    <h4 class="mt-4 text-base font-semibold text-navy">{{ $content['turning']['left_title'] }}</h4>
                                    <p class="mt-2 text-sm leading-relaxed text-navy-muted">{{ $content['turning']['left_body'] }}</p>
                                </div>
                                <div>
                                    <x-learn.guide-figure asset-key="right_turn" :assets="$assets" :captions="$captions" :alts="$alts" />
                                    <h4 class="mt-4 text-base font-semibold text-navy">{{ $content['turning']['right_title'] }}</h4>
                                    <p class="mt-2 text-sm leading-relaxed text-navy-muted">{{ $content['turning']['right_body'] }}</p>
                                </div>
                            </div>
                        </noscript>
                    </div>
                    <p class="text-sm leading-relaxed text-navy-muted">{{ $content['turning']['law_note'] }}</p>
                    <p class="rounded-2xl border border-amber/40 bg-amber-soft px-4 py-3 text-sm leading-relaxed text-navy">{{ $content['turning']['tip'] }}</p>
                </section>

                <section id="uncontrolled" class="motion-reveal-up scroll-mt-24 space-y-5" data-reveal>
                    <h2 class="text-2xl font-bold text-navy">{{ $content['uncontrolled']['title'] }}</h2>
                    <p class="font-medium leading-relaxed text-navy">{{ $content['uncontrolled']['lead'] }}</p>
                    <x-learn.guide-figure asset-key="uncontrolled" :assets="$assets" :captions="$captions" :alts="$alts" :figure-label="$content['figure_label']" :figure-number="8" />
                    <p class="leading-relaxed text-navy-muted">{{ $content['uncontrolled']['p1'] }}</p>
                    <p class="leading-relaxed text-navy-muted">{{ $content['uncontrolled']['p2'] }}</p>
                    <p class="leading-relaxed text-navy-muted">{{ $content['uncontrolled']['p3'] }}</p>
                    <p class="rounded-2xl border border-matcha/30 bg-matcha/10 px-4 py-3 text-sm leading-relaxed text-navy">{{ $content['uncontrolled']['tip'] }}</p>
                </section>

                <section id="mistakes" class="motion-reveal-up scroll-mt-24 space-y-5" data-reveal>
                    <h2 class="text-2xl font-bold text-navy">{{ $content['mistakes']['title'] }}</h2>
                    <p class="leading-relaxed text-navy-muted">{{ $content['mistakes']['lead'] }}</p>
                    <x-learn.guide-figure asset-key="mistakes" :assets="$assets" :captions="$captions" :alts="$alts" :figure-label="$content['figure_label']" :figure-number="9" />
                    <div class="space-y-3">
                        @foreach ($content['mistakes']['items'] ?? [] as $mistake)
                            <article class="rounded-3xl border border-border bg-white p-5 shadow-soft" x-data="{ open: false }">
                                <button type="button" class="flex w-full items-start justify-between gap-3 text-left" @click="open = ! open" :aria-expanded="open.toString()">
                                    <span>
                                        <span class="block text-xs font-semibold uppercase tracking-wide text-vermilion">×</span>
                                        <span class="mt-1 block font-semibold text-navy">{{ $mistake['wrong'] }}</span>
                                    </span>
                                    <span class="text-sm text-accent" x-text="open ? '−' : '+'" aria-hidden="true"></span>
                                </button>
                                <div x-show="open" x-cloak x-transition.opacity.duration.200ms class="mt-3 border-t border-border pt-3">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-matcha">○</p>
                                    <p class="mt-1 text-sm leading-relaxed text-navy-muted">{{ $mistake['right'] }}</p>
                                </div>
                                <noscript>
                                    <p class="mt-3 text-sm leading-relaxed text-navy-muted">{{ $mistake['right'] }}</p>
                                </noscript>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section id="scenarios" class="motion-reveal-up scroll-mt-24 space-y-5" data-reveal>
                    <h2 class="text-2xl font-bold text-navy">{{ $content['scenarios']['title'] }}</h2>
                    <p class="leading-relaxed text-navy-muted">{{ $content['scenarios']['lead'] }}</p>
                    <div
                        class="rounded-3xl border border-border bg-white p-5 shadow-soft"
                        x-data="{ showHazards: false }"
                        data-hazard-reveal
                    >
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h3 class="text-lg font-semibold text-navy">{{ $content['interaction_hazard_title'] }}</h3>
                            <button type="button" class="btn-secondary min-h-11" @click="showHazards = ! showHazards">
                                <span x-text="showHazards ? @js($content['interaction_hazard_hide']) : @js($content['interaction_hazard_show'])"></span>
                            </button>
                        </div>
                        <div class="mt-4" :class="showHazards ? 'opacity-100' : 'opacity-90'">
                            <x-learn.guide-figure asset-key="hazard" :assets="$assets" :captions="$captions" :alts="$alts" :figure-label="$content['figure_label']" :figure-number="10" />
                        </div>
                    </div>
                    <div class="space-y-4">
                        @foreach ($content['scenarios']['items'] ?? [] as $scenario)
                            <article class="rounded-3xl border border-border bg-gradient-to-br from-white to-surface p-5 shadow-soft sm:p-6" x-data="{ open: false }" data-scenario-card>
                                <h3 class="text-lg font-semibold text-navy">{{ $scenario['title'] }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-navy-muted">{{ $scenario['setup'] }}</p>
                                @if (! empty($scenario['asset']))
                                    <div class="mt-4">
                                        <x-learn.guide-figure :asset-key="$scenario['asset']" :assets="$assets" :captions="$captions" :alts="$alts" />
                                    </div>
                                @endif
                                <p class="mt-3 font-medium text-navy">{{ $scenario['ask'] }}</p>
                                <button type="button" class="btn-secondary mt-4 min-h-11" @click="open = ! open" :aria-expanded="open.toString()">
                                    <span x-text="open ? @js($content['scenarios']['collapse_label']) : @js($content['scenarios']['expand_label'])"></span>
                                </button>
                                <div x-show="open" x-cloak x-transition.opacity.duration.200ms class="mt-4 rounded-2xl border border-matcha/30 bg-matcha/10 px-4 py-3 text-sm leading-relaxed text-navy">
                                    {{ $scenario['reveal'] }}
                                </div>
                                <noscript>
                                    <p class="mt-4 text-sm leading-relaxed text-navy">{{ $scenario['reveal'] }}</p>
                                </noscript>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section id="keypoints" class="motion-reveal-up scroll-mt-24 space-y-4" data-reveal>
                    <h2 class="text-2xl font-bold text-navy">{{ $content['keypoints']['title'] }}</h2>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($content['keypoints']['items'] ?? [] as $point)
                            <article class="rounded-3xl border border-border bg-white p-5 shadow-soft">
                                <h3 class="font-semibold text-navy">{{ $point['title'] }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-navy-muted">{{ $point['body'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section id="faq" class="motion-reveal-up scroll-mt-24 space-y-4" data-reveal>
                    <h2 class="text-2xl font-bold text-navy">{{ $content['faq_heading'] }}</h2>
                    <div class="space-y-3">
                        @foreach ($content['faq'] ?? [] as $faq)
                            <details class="group rounded-3xl border border-border bg-white p-5 shadow-soft">
                                <summary class="cursor-pointer list-none font-semibold text-navy marker:content-none [&::-webkit-details-marker]:hidden">
                                    <span class="flex items-start justify-between gap-3">
                                        <span>{{ $faq['q'] }}</span>
                                        <span class="text-accent transition group-open:rotate-45" aria-hidden="true">+</span>
                                    </span>
                                </summary>
                                <p class="mt-3 text-sm leading-relaxed text-navy-muted">{{ $faq['a'] }}</p>
                            </details>
                        @endforeach
                    </div>
                </section>

                <section id="sources" class="motion-reveal-up scroll-mt-24 space-y-4" data-reveal data-guide-sources>
                    <h2 class="text-2xl font-bold text-navy">{{ $content['sources_heading'] }}</h2>
                    <p class="leading-relaxed text-navy-muted">{{ $content['sources_intro'] }}</p>
                    <ul class="space-y-4">
                        @foreach ($sources as $source)
                            <li class="rounded-3xl border border-border bg-paper p-5 shadow-soft">
                                <p class="font-semibold text-navy">{{ $source['title'] }}</p>
                                <p class="mt-1 text-sm text-navy-muted">{{ $content['source_org'] }}: {{ $source['organization'] }}</p>
                                <p class="mt-1 text-sm text-navy-muted">{{ $content['source_section'] }}: {{ $source['section'] }}</p>
                                <p class="mt-1 text-sm text-navy-muted">{{ $content['source_checked'] }}: {{ $source['checked_at'] }}</p>
                                <a href="{{ $source['url'] }}" class="mt-3 inline-flex text-sm font-semibold text-accent hover:text-accent-dark" rel="noopener noreferrer" target="_blank">
                                    {{ $content['source_open'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>

                <section id="related" class="motion-reveal-up scroll-mt-24 space-y-4" data-reveal>
                    <h2 class="text-2xl font-bold text-navy">{{ $content['related_heading'] }}</h2>
                    <p class="leading-relaxed text-navy-muted">{{ $content['related_intro'] }}</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <a href="{{ route('learn.traffic-rules.pedestrian-priority') }}" class="rounded-2xl border border-border bg-white p-4 shadow-soft transition hover:border-accent">
                            <img src="{{ asset(config('content.pedestrian_priority.thumbnail')) }}" alt="" width="320" height="160" class="mb-3 h-24 w-full rounded-xl bg-washi object-contain" loading="lazy" decoding="async">
                            <p class="font-semibold text-navy">{{ $content['related']['pedestrian']['title'] }}</p>
                            <p class="mt-1 text-sm text-navy-muted">{{ $content['related']['pedestrian']['desc'] }}</p>
                        </a>
                        <a href="{{ route('learn.traffic-rules') }}" class="rounded-2xl border border-border bg-white p-4 shadow-soft transition hover:border-accent">
                            <p class="font-semibold text-navy">{{ $content['related']['rules']['title'] }}</p>
                            <p class="mt-1 text-sm text-navy-muted">{{ $content['related']['rules']['desc'] }}</p>
                        </a>
                        <a href="{{ route('learn.road-signs') }}#sign-temporary-stop" class="rounded-2xl border border-border bg-white p-4 shadow-soft transition hover:border-accent">
                            <p class="font-semibold text-navy">{{ $content['related']['signs']['title'] }}</p>
                            <p class="mt-1 text-sm text-navy-muted">{{ $content['related']['signs']['desc'] }}</p>
                        </a>
                        <a href="{{ route('learn.hazard-prediction') }}" class="rounded-2xl border border-border bg-white p-4 shadow-soft transition hover:border-accent">
                            <p class="font-semibold text-navy">{{ $content['related']['hazard']['title'] }}</p>
                            <p class="mt-1 text-sm text-navy-muted">{{ $content['related']['hazard']['desc'] }}</p>
                        </a>
                        <a href="{{ route('exams.karimen') }}" class="rounded-2xl border border-border bg-white p-4 shadow-soft transition hover:border-accent">
                            <p class="font-semibold text-navy">{{ $content['related']['karimen']['title'] }}</p>
                            <p class="mt-1 text-sm text-navy-muted">{{ $content['related']['karimen']['desc'] }}</p>
                        </a>
                        <a href="{{ route('exams.honmen') }}" class="rounded-2xl border border-border bg-white p-4 shadow-soft transition hover:border-accent">
                            <p class="font-semibold text-navy">{{ $content['related']['honmen']['title'] }}</p>
                            <p class="mt-1 text-sm text-navy-muted">{{ $content['related']['honmen']['desc'] }}</p>
                        </a>
                    </div>
                    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                        <a href="{{ route('exams.karimen') }}" class="btn-primary inline-flex min-h-11 items-center justify-center">{{ $content['cta_practice'] }}</a>
                        <a href="{{ route('learn.traffic-rules.pedestrian-priority') }}" class="btn-secondary inline-flex min-h-11 items-center justify-center">{{ $content['cta_pedestrian'] }}</a>
                        <a href="{{ route('learn.traffic-rules') }}" class="btn-secondary inline-flex min-h-11 items-center justify-center">{{ $content['cta_rules'] }}</a>
                    </div>
                </section>

                <x-ui.disclaimer-notice />
            </article>

            <aside class="min-w-0 space-y-5 lg:col-span-4 lg:sticky lg:top-24 lg:self-start" aria-label="{{ $content['related_heading'] }}">
                <div class="rounded-3xl border border-border bg-white p-5 shadow-soft" x-data="{ done: {} }" data-guide-progress>
                    <h2 class="text-lg font-semibold text-navy">{{ $content['progress_label'] }}</h2>
                    <p class="mt-2 text-xs leading-relaxed text-navy-muted">{{ $content['progress_hint'] }}</p>
                    <ul class="mt-4 space-y-3">
                        @foreach ($content['progress_items'] ?? [] as $index => $item)
                            <li>
                                <label class="flex cursor-pointer items-start gap-3 text-sm leading-relaxed text-navy">
                                    <input type="checkbox" class="mt-1 h-4 w-4 rounded border-border text-matcha focus:ring-matcha" x-model="done[{{ $index }}]">
                                    <span>{{ $item }}</span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <a href="{{ route('learn.traffic-rules.pedestrian-priority') }}" class="block overflow-hidden rounded-3xl border border-border bg-white shadow-soft transition hover:border-accent">
                    <img src="{{ asset(config('content.pedestrian_priority.thumbnail')) }}" alt="" width="480" height="240" class="h-32 w-full bg-washi object-contain" loading="lazy" decoding="async">
                    <div class="p-4">
                        <p class="font-semibold text-navy">{{ $content['related']['pedestrian']['title'] }}</p>
                        <p class="mt-1 text-sm text-navy-muted">{{ $content['related']['pedestrian']['desc'] }}</p>
                    </div>
                </a>
                <x-ui.resource-card
                    :title="$content['related']['karimen']['title']"
                    :description="$content['related']['karimen']['desc']"
                    :href="route('exams.karimen')"
                    icon="book"
                    accent="blue"
                    :equal-height="false"
                />
                @if ($showSidebarAd)
                    <x-ui.ad-slot name="sidebar" class="hidden lg:block" />
                @endif
            </aside>
        </div>

        @if ($showBottomAd)
            <div class="section-shell mt-10">
                <x-ui.ad-slot name="content-bottom" />
            </div>
        @endif
    </div>
</x-layouts.site>
