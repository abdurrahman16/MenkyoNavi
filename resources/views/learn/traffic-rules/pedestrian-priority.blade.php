@php
    use App\Support\Advertising;

    /** @var array<string, mixed> $content */
    /** @var array<string, mixed> $manifest */
    /** @var list<array<string, mixed>> $sources */
    $diagrams = $manifest['diagrams'] ?? [];
    $showMidAd = Advertising::shouldRenderSlot('content-mid');
    $showInlineAd = Advertising::shouldRenderSlot('article-inline');
    $showSidebarAd = Advertising::shouldRenderSlot('sidebar');
    $showBottomAd = Advertising::shouldRenderSlot('content-bottom');
    $toc = $content['toc'] ?? [];
@endphp

<x-layouts.site
    :title="$content['meta_title']"
    :description="$content['meta_description']"
    :canonical="route('learn.traffic-rules.pedestrian-priority')"
    :robots="$robots"
    :json-ld="$jsonLd"
>
    <x-ui.breadcrumb :items="[
        ['label' => __('pages.home'), 'url' => route('home')],
        ['label' => __('pages.learn.breadcrumb'), 'url' => route('learn.index')],
        ['label' => __('pages.traffic_rules.breadcrumb'), 'url' => route('learn.traffic-rules')],
        ['label' => $content['breadcrumb'], 'url' => route('learn.traffic-rules.pedestrian-priority')],
    ]" />

    <section class="relative overflow-hidden border-b border-border gradient-hero py-10 sm:py-12" data-guide-hero>
        <div class="section-shell relative grid items-center gap-8 lg:grid-cols-12 lg:gap-10">
            <div class="min-w-0 lg:col-span-7">
                <p class="section-label">{{ $content['hero_eyebrow'] }}</p>
                <h1 class="mt-2 text-balance text-3xl font-bold tracking-tight text-navy sm:text-4xl">
                    {{ $content['title'] }}
                </h1>
                <div class="motion-divider" aria-hidden="true"></div>
                <p class="mt-4 max-w-2xl text-base leading-relaxed text-navy-muted sm:text-lg">
                    {{ $content['subtitle'] }}
                </p>
                <div class="mt-4 flex flex-wrap gap-3 text-xs font-medium text-navy-muted sm:text-sm">
                    <span class="rounded-full border border-border bg-paper/80 px-3 py-1">{{ $content['reading_time'] }}</span>
                    <span class="rounded-full border border-border bg-paper/80 px-3 py-1">
                        {{ $content['updated_label'] }}: {{ $manifest['updated_at'] ?? '' }}
                    </span>
                </div>
            </div>
            <div class="motion-reveal-scale min-w-0 lg:col-span-5" data-reveal>
                <figure class="overflow-hidden rounded-3xl border border-border bg-paper shadow-lift">
                    <img
                        src="{{ asset($diagrams['approach'] ?? 'images/learn/pedestrian-priority-approach.svg') }}"
                        alt="{{ $content['visuals']['alts']['approach'] ?? '' }}"
                        width="640"
                        height="320"
                        class="h-auto w-full object-contain bg-washi/40"
                        decoding="async"
                    />
                    <figcaption class="border-t border-border px-4 py-3 text-sm text-navy-muted">
                        {{ $content['visuals']['captions']['approach'] ?? '' }}
                    </figcaption>
                </figure>
            </div>
        </div>
    </section>

    <div class="section-pad section-paper">
        <div class="section-shell grid items-start gap-8 lg:grid-cols-12 lg:gap-10">
            <article class="min-w-0 space-y-10 lg:col-span-8" data-educational-guide>
                @if (! empty($content['draft_notice']) || $usingFallback)
                    <p class="rounded-2xl border border-amber/40 bg-amber-soft px-4 py-3 text-sm leading-relaxed text-navy" role="status">
                        {{ $content['draft_notice'] ?: __('pages.traffic_rules.empty_body') }}
                    </p>
                @endif

                <p class="text-base leading-relaxed text-navy-muted sm:text-lg" id="guide-intro">
                    {{ $content['intro'] }}
                </p>

                <nav
                    class="rounded-3xl border border-navy/15 bg-gradient-to-br from-washi via-paper to-surface p-5 shadow-soft sm:p-6"
                    aria-label="{{ $content['toc_label'] }}"
                    data-guide-toc
                >
                    <h2 class="text-sm font-semibold uppercase tracking-[0.14em] text-accent">{{ $content['toc_label'] }}</h2>
                    <ol class="mt-4 grid gap-2 sm:grid-cols-2">
                        @foreach ($toc as $anchor => $label)
                            <li>
                                <a
                                    href="#{{ $anchor }}"
                                    class="block rounded-xl px-3 py-2 text-sm font-medium text-navy transition hover:bg-white hover:text-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                                >
                                    {{ $label }}
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </nav>

                @if ($showMidAd)
                    <x-ui.ad-slot name="content-mid" class="mx-0" />
                @endif

                <section id="meaning" class="motion-reveal-up scroll-mt-24 space-y-4" data-reveal>
                    <h2 class="text-2xl font-bold text-navy">{{ $content['meaning']['title'] }}</h2>
                    <p class="text-base font-medium leading-relaxed text-navy">{{ $content['meaning']['lead'] }}</p>
                    <p class="leading-relaxed text-navy-muted">{{ $content['meaning']['p1'] }}</p>
                    <p class="leading-relaxed text-navy-muted">{{ $content['meaning']['p2'] }}</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <aside class="rounded-2xl border border-navy/20 bg-navy/5 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-accent">{{ $content['legal_label'] }}</p>
                            <p class="mt-2 text-sm leading-relaxed text-navy">{{ $content['meaning']['law_note'] }}</p>
                        </aside>
                        <aside class="rounded-2xl border border-matcha/30 bg-matcha/10 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-matcha">{{ $content['tip_label'] }}</p>
                            <p class="mt-2 text-sm leading-relaxed text-navy">{{ $content['meaning']['recommend_note'] }}</p>
                        </aside>
                    </div>
                </section>

                <section id="principles" class="motion-reveal-up scroll-mt-24 space-y-5" data-reveal>
                    <h2 class="text-2xl font-bold text-navy">{{ $content['principles']['title'] }}</h2>
                    <p class="leading-relaxed text-navy-muted">{{ $content['principles']['lead'] }}</p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($content['principles']['items'] ?? [] as $item)
                            <article class="rounded-3xl border border-border bg-white p-5 shadow-soft">
                                <p class="text-xs font-semibold uppercase tracking-wide {{ ($item['type'] ?? '') === 'legal' ? 'text-accent' : 'text-matcha' }}">
                                    {{ ($item['type'] ?? '') === 'legal' ? $content['legal_label'] : $content['tip_label'] }}
                                </p>
                                <h3 class="mt-2 text-lg font-semibold text-navy">{{ $item['title'] }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-navy-muted">{{ $item['body'] }}</p>
                            </article>
                        @endforeach
                    </div>
                    <div class="overflow-x-auto rounded-3xl border border-border bg-paper shadow-soft">
                        <table class="min-w-full text-left text-sm">
                            <caption class="sr-only">{{ $content['principles']['table_title'] }}</caption>
                            <thead class="bg-navy/5 text-navy-muted">
                                <tr>
                                    @foreach ($content['principles']['table_headers'] ?? [] as $header)
                                        <th scope="col" class="px-4 py-3 font-semibold">{{ $header }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($content['principles']['table_rows'] ?? [] as $row)
                                    <tr class="border-t border-border">
                                        <th scope="row" class="px-4 py-3 font-medium text-navy">{{ $row[0] ?? '' }}</th>
                                        <td class="px-4 py-3 text-navy-muted">{{ $row[1] ?? '' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                <section id="observation" class="motion-reveal-up scroll-mt-24 space-y-4" data-reveal>
                    <h2 class="text-2xl font-bold text-navy">{{ $content['observation']['title'] }}</h2>
                    <p class="leading-relaxed text-navy-muted">{{ $content['observation']['lead'] }}</p>
                    <p class="leading-relaxed text-navy-muted">{{ $content['observation']['p1'] }}</p>
                    <p class="leading-relaxed text-navy-muted">{{ $content['observation']['p2'] }}</p>
                    <div
                        class="rounded-3xl border border-border bg-white p-5 shadow-soft sm:p-6"
                        x-data="{ checked: {} }"
                        data-learning-checklist
                    >
                        <h3 class="text-lg font-semibold text-navy">{{ $content['observation']['checklist_title'] }}</h3>
                        <ul class="mt-4 space-y-3">
                            @foreach ($content['observation']['checklist'] ?? [] as $index => $item)
                                <li>
                                    <label class="flex cursor-pointer items-start gap-3 rounded-2xl bg-surface/80 px-3 py-3 text-sm leading-relaxed text-navy">
                                        <input
                                            type="checkbox"
                                            class="mt-1 h-4 w-4 rounded border-border text-accent focus:ring-accent"
                                            x-model="checked[{{ $index }}]"
                                        >
                                        <span>{{ $item }}</span>
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </section>

                @if ($showInlineAd)
                    <x-ui.ad-slot name="article-inline" class="mx-0" />
                @endif

                <section id="preparing" class="motion-reveal-up scroll-mt-24 space-y-4" data-reveal>
                    <h2 class="text-2xl font-bold text-navy">{{ $content['preparing']['title'] }}</h2>
                    <p class="font-medium leading-relaxed text-navy">{{ $content['preparing']['lead'] }}</p>
                    <p class="leading-relaxed text-navy-muted">{{ $content['preparing']['p1'] }}</p>
                    <p class="leading-relaxed text-navy-muted">{{ $content['preparing']['p2'] }}</p>
                    <p class="rounded-2xl border-l-4 border-vermilion bg-vermilion-soft px-4 py-3 text-sm font-medium leading-relaxed text-navy">
                        {{ $content['preparing']['highlight'] }}
                    </p>
                    <figure class="overflow-hidden rounded-3xl border border-border bg-paper shadow-soft">
                        <img
                            src="{{ asset($diagrams['preparing'] ?? '') }}"
                            alt="{{ $content['visuals']['alts']['preparing'] ?? '' }}"
                            width="640"
                            height="320"
                            class="h-auto w-full object-contain bg-washi/40"
                            loading="lazy"
                            decoding="async"
                        />
                        <figcaption class="border-t border-border px-4 py-3 text-sm text-navy-muted">
                            {{ $content['visuals']['captions']['preparing'] ?? '' }}
                        </figcaption>
                    </figure>
                </section>

                <section id="unsignalized" class="motion-reveal-up scroll-mt-24 space-y-4" data-reveal>
                    <h2 class="text-2xl font-bold text-navy">{{ $content['unsignalized']['title'] }}</h2>
                    <p class="font-medium leading-relaxed text-navy">{{ $content['unsignalized']['lead'] }}</p>
                    <p class="leading-relaxed text-navy-muted">{{ $content['unsignalized']['p1'] }}</p>
                    <p class="leading-relaxed text-navy-muted">{{ $content['unsignalized']['p2'] }}</p>
                    <p class="rounded-2xl border border-amber/40 bg-amber-soft px-4 py-3 text-sm leading-relaxed text-navy">
                        {{ $content['unsignalized']['tip'] }}
                    </p>
                </section>

                <section id="mistakes" class="motion-reveal-up scroll-mt-24 space-y-4" data-reveal>
                    <h2 class="text-2xl font-bold text-navy">{{ $content['mistakes']['title'] }}</h2>
                    <p class="leading-relaxed text-navy-muted">{{ $content['mistakes']['lead'] }}</p>
                    <div class="space-y-3">
                        @foreach ($content['mistakes']['items'] ?? [] as $mistake)
                            <article
                                class="rounded-3xl border border-border bg-white p-5 shadow-soft"
                                x-data="{ open: false }"
                            >
                                <button
                                    type="button"
                                    class="flex w-full items-start justify-between gap-3 text-left"
                                    @click="open = ! open"
                                    :aria-expanded="open.toString()"
                                >
                                    <span>
                                        <span class="block text-xs font-semibold uppercase tracking-wide text-vermilion">×</span>
                                        <span class="mt-1 block font-semibold text-navy">{{ $mistake['wrong'] }}</span>
                                    </span>
                                    <span class="text-sm text-accent" x-text="open ? '−' : '+'" aria-hidden="true"></span>
                                </button>
                                <div
                                    x-show="open"
                                    x-cloak
                                    x-transition.opacity.duration.200ms
                                    class="mt-3 border-t border-border pt-3"
                                >
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
                    <div class="space-y-4">
                        @foreach ($content['scenarios']['items'] ?? [] as $scenario)
                            <article
                                class="rounded-3xl border border-border bg-gradient-to-br from-white to-surface p-5 shadow-soft sm:p-6"
                                x-data="{ open: false }"
                                data-scenario-card
                            >
                                <h3 class="text-lg font-semibold text-navy">{{ $scenario['title'] }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-navy-muted">{{ $scenario['setup'] }}</p>
                                <p class="mt-3 font-medium text-navy">{{ $scenario['ask'] }}</p>
                                <button
                                    type="button"
                                    class="btn-secondary mt-4 min-h-11"
                                    @click="open = ! open"
                                    :aria-expanded="open.toString()"
                                >
                                    <span x-text="open ? @js($content['scenarios']['collapse_label']) : @js($content['scenarios']['expand_label'])"></span>
                                </button>
                                <div
                                    x-show="open"
                                    x-cloak
                                    x-transition.opacity.duration.200ms
                                    class="mt-4 rounded-2xl border border-matcha/30 bg-matcha/10 px-4 py-3 text-sm leading-relaxed text-navy"
                                >
                                    {{ $scenario['reveal'] }}
                                </div>
                                <noscript>
                                    <p class="mt-4 text-sm leading-relaxed text-navy">{{ $scenario['reveal'] }}</p>
                                </noscript>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section id="visuals" class="motion-reveal-up scroll-mt-24 space-y-5" data-reveal>
                    <h2 class="text-2xl font-bold text-navy">{{ $content['visuals']['title'] }}</h2>
                    <p class="leading-relaxed text-navy-muted">{{ $content['visuals']['lead'] }}</p>
                    <div class="grid gap-5 sm:grid-cols-2">
                        @foreach (['sightlines', 'intersection'] as $key)
                            <figure class="overflow-hidden rounded-3xl border border-border bg-paper shadow-soft">
                                <img
                                    src="{{ asset($diagrams[$key] ?? '') }}"
                                    alt="{{ $content['visuals']['alts'][$key] ?? '' }}"
                                    width="640"
                                    height="320"
                                    class="h-auto w-full object-contain bg-washi/40"
                                    loading="lazy"
                                    decoding="async"
                                />
                                <figcaption class="border-t border-border px-4 py-3 text-sm text-navy-muted">
                                    {{ $content['visuals']['captions'][$key] ?? '' }}
                                </figcaption>
                            </figure>
                        @endforeach
                    </div>
                </section>

                <section id="keypoints" class="motion-reveal-up scroll-mt-24 space-y-4" data-reveal>
                    <h2 class="text-2xl font-bold text-navy">{{ $content['keypoints']['title'] }}</h2>
                    <ol class="space-y-3">
                        @foreach ($content['keypoints']['items'] ?? [] as $point)
                            <li class="flex gap-3 rounded-2xl border border-border bg-white px-4 py-3 text-sm leading-relaxed text-navy shadow-soft">
                                <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-navy text-xs font-bold text-white" aria-hidden="true">✓</span>
                                <span>{{ $point }}</span>
                            </li>
                        @endforeach
                    </ol>
                </section>

                <section id="practice" class="motion-reveal-up scroll-mt-24 space-y-5" data-reveal data-educational-practice>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-accent">{{ $content['practice']['badge'] }}</p>
                        <h2 class="mt-2 text-2xl font-bold text-navy">{{ $content['practice']['title'] }}</h2>
                        <p class="mt-2 leading-relaxed text-navy-muted">{{ $content['practice']['lead'] }}</p>
                    </div>
                    <div class="space-y-4">
                        @foreach ($content['practice']['items'] ?? [] as $index => $item)
                            <article
                                class="rounded-3xl border border-border bg-white p-5 shadow-soft sm:p-6"
                                x-data="{ open: false }"
                            >
                                <h3 class="text-base font-semibold text-navy">Q{{ $index + 1 }}. {{ $item['q'] }}</h3>
                                <ul class="mt-4 space-y-2">
                                    @foreach ($item['choices'] ?? [] as $choiceIndex => $choice)
                                        <li
                                            class="rounded-2xl border border-border bg-surface px-4 py-3 text-sm leading-relaxed text-navy-muted"
                                            :class="open && {{ $choiceIndex === ($item['answer'] ?? -1) ? 'true' : 'false' }}
                                                ? 'border-matcha/40 bg-matcha/10 font-medium text-navy'
                                                : 'border-border bg-surface text-navy-muted'"
                                        >
                                            {{ $choice }}
                                        </li>
                                    @endforeach
                                </ul>
                                <button
                                    type="button"
                                    class="btn-secondary mt-4 min-h-11"
                                    @click="open = ! open"
                                    :aria-expanded="open.toString()"
                                >
                                    <span x-text="open ? @js($content['practice']['hide_answer']) : @js($content['practice']['show_answer'])"></span>
                                </button>
                                <div
                                    x-show="open"
                                    x-cloak
                                    x-transition.opacity.duration.200ms
                                    class="mt-3 text-sm leading-relaxed text-navy-muted"
                                >
                                    <p class="font-semibold text-navy">{{ $content['practice']['correct_label'] }}</p>
                                    <p class="mt-1">{{ $item['explain'] }}</p>
                                </div>
                                <noscript>
                                    <p class="mt-3 text-sm leading-relaxed text-navy-muted">
                                        <span class="font-semibold text-navy">{{ $content['practice']['correct_label'] }}: </span>
                                        {{ $item['choices'][$item['answer'] ?? 0] ?? '' }} — {{ $item['explain'] }}
                                    </p>
                                </noscript>
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
                                <a
                                    href="{{ $source['url'] }}"
                                    class="mt-3 inline-flex text-sm font-semibold text-accent hover:text-accent-dark"
                                    rel="noopener noreferrer"
                                    target="_blank"
                                >
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
                        <a href="{{ route('learn.traffic-rules.intersection-priority') }}" class="rounded-2xl border border-border bg-white p-4 shadow-soft transition hover:border-accent">
                            <img src="{{ asset(config('content.intersection_priority.thumbnail')) }}" alt="" width="320" height="160" class="mb-3 h-24 w-full rounded-xl bg-washi object-contain" loading="lazy" decoding="async">
                            <p class="font-semibold text-navy">{{ __('pages.traffic_rules.featured2_title') }}</p>
                            <p class="mt-1 text-sm text-navy-muted">{{ __('pages.traffic_rules.featured2_body') }}</p>
                        </a>
                        <a href="{{ route('learn.traffic-rules') }}" class="rounded-2xl border border-border bg-white p-4 shadow-soft transition hover:border-accent">
                            <p class="font-semibold text-navy">{{ $content['related']['rules']['title'] }}</p>
                            <p class="mt-1 text-sm text-navy-muted">{{ $content['related']['rules']['desc'] }}</p>
                        </a>
                        <a href="{{ route('learn.road-signs') }}#sign-pedestrian-crossing" class="rounded-2xl border border-border bg-white p-4 shadow-soft transition hover:border-accent">
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
                        <a href="{{ route('learn.index') }}" class="rounded-2xl border border-border bg-white p-4 shadow-soft transition hover:border-accent">
                            <p class="font-semibold text-navy">{{ $content['related']['learn']['title'] }}</p>
                            <p class="mt-1 text-sm text-navy-muted">{{ $content['related']['learn']['desc'] }}</p>
                        </a>
                    </div>
                    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                        <a href="{{ route('exams.karimen') }}" class="btn-primary inline-flex min-h-11 items-center justify-center">{{ $content['cta_practice'] }}</a>
                        <a href="{{ route('exams.honmen') }}" class="btn-secondary inline-flex min-h-11 items-center justify-center">{{ $content['cta_honmen'] }}</a>
                        <a href="{{ route('learn.traffic-rules') }}" class="btn-secondary inline-flex min-h-11 items-center justify-center">{{ $content['cta_rules'] }}</a>
                    </div>
                </section>

                <x-ui.disclaimer-notice />
            </article>

            <aside class="min-w-0 space-y-5 lg:col-span-4 lg:sticky lg:top-24 lg:self-start" aria-label="{{ $content['related_heading'] }}">
                <div
                    class="rounded-3xl border border-border bg-white p-5 shadow-soft"
                    x-data="{ done: {} }"
                    data-guide-progress
                >
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

                <x-ui.resource-card
                    :title="$content['related']['karimen']['title']"
                    :description="$content['related']['karimen']['desc']"
                    :href="route('exams.karimen')"
                    icon="book"
                    accent="blue"
                    :equal-height="false"
                />
                <x-ui.resource-card
                    :title="$content['related']['hazard']['title']"
                    :description="$content['related']['hazard']['desc']"
                    :href="route('learn.hazard-prediction')"
                    icon="alert"
                    accent="teal"
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
