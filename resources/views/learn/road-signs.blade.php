@php
    use App\Support\Advertising;
    use App\Support\Content\RoadSignsGuide;

    /** @var array<string, mixed> $content */
    /** @var array<string, mixed> $manifest */
    /** @var list<array<string, mixed>> $sources */
    /** @var list<array<string, mixed>> $signs */
    /** @var list<array<string, mixed>> $allSigns */
    /** @var list<array<string, mixed>> $practiceItems */
    $diagrams = $manifest['diagrams'] ?? [];
    $assets = $manifest['assets'] ?? [];
    $showMidAd = Advertising::shouldRenderSlot('content-mid');
    $showInlineAd = Advertising::shouldRenderSlot('article-inline');
    $showBottomAd = Advertising::shouldRenderSlot('content-bottom');
    $toc = $content['toc'] ?? [];
    $categoryKeys = RoadSignsGuide::CATEGORY_KEYS;
    $practicePayload = collect($practiceItems)->map(function (array $item) use ($allSigns) {
        $signId = (string) ($item['sign_id'] ?? '');
        $sign = collect($allSigns)->firstWhere('id', $signId);
        $illustration = (string) ($sign['illustration'] ?? '');

        return [
            'sign_id' => $signId,
            'q' => $item['q'] ?? '',
            'choices' => array_values($item['choices'] ?? []),
            'answer' => (int) ($item['answer'] ?? 0),
            'explain' => $item['explain'] ?? '',
            'illustration' => $illustration !== '' ? asset($illustration) : '',
            'alt' => $sign['alt'] ?? '',
            'official_name_ja' => $sign['official_name_ja'] ?? '',
        ];
    })->values()->all();

    $filters = ['all' => $content['filter_all']];
    foreach ($categoryKeys as $key) {
        $filters[$key] = $content['categories'][$key]['title'] ?? $key;
    }

    $filterChipClass = [
        'all' => 'data-[active=true]:border-accent data-[active=true]:bg-accent data-[active=true]:text-white',
        'regulatory' => 'data-[active=true]:border-vermilion data-[active=true]:bg-vermilion data-[active=true]:text-white',
        'warning' => 'data-[active=true]:border-gold data-[active=true]:bg-gold data-[active=true]:text-navy',
        'instruction' => 'data-[active=true]:border-accent data-[active=true]:bg-accent data-[active=true]:text-white',
        'guide' => 'data-[active=true]:border-accent-dark data-[active=true]:bg-accent-dark data-[active=true]:text-white',
        'supplementary' => 'data-[active=true]:border-matcha data-[active=true]:bg-matcha data-[active=true]:text-white',
    ];

    $sourcesById = collect($sources)->keyBy(fn (array $source): string => (string) ($source['id'] ?? ''))->all();

    $signCardLabels = [
        'open' => $content['open_details'],
        'close' => $content['close_details'],
        'official_name' => $content['official_name_label'],
        'category' => $content['category_label'],
        'meaning' => $content['meaning_label'],
        'must_do' => $content['must_do_label'],
        'example' => $content['example_label'],
        'misconception' => $content['misconception_label'],
        'related' => $content['related_label'],
        'source' => $content['source_label'],
    ];

    unset($toc['details']);

    $filteredSignIds = collect($signs)->pluck('id')->all();
    $hasServerFilter = ($activeCategory ?? 'all') !== 'all' || filled($searchQuery);
@endphp

<x-layouts.site
    :title="$content['meta_title']"
    :description="$content['meta_description']"
    :canonical="route('learn.road-signs')"
    :robots="$robots"
    :json-ld="$jsonLd"
>
    <x-ui.breadcrumb :items="[
        ['label' => __('pages.home'), 'url' => route('home')],
        ['label' => __('pages.learn.breadcrumb'), 'url' => route('learn.index')],
        ['label' => $content['breadcrumb'], 'url' => route('learn.road-signs')],
    ]" />

    {{-- 1. Hero --}}
    <section class="relative overflow-hidden border-b border-border gradient-hero py-10 sm:py-12" data-guide-hero>
        <div class="section-shell relative grid items-center gap-8 lg:grid-cols-12 lg:gap-10">
            <div class="min-w-0 lg:col-span-7">
                <p class="section-label">{{ $content['hero_eyebrow'] }}</p>
                <h1 class="mt-2 text-balance text-3xl font-bold tracking-tight text-navy sm:text-4xl">
                    {{ $content['title'] }}
                </h1>
                <div class="motion-divider" aria-hidden="true"></div>
                <p class="mt-4 max-w-2xl text-base leading-relaxed text-navy/80 sm:text-lg">
                    {{ $content['subtitle'] }}
                </p>
                <div class="mt-4 flex flex-wrap gap-3 text-xs font-medium text-navy/70 sm:text-sm">
                    <span class="rounded-full border border-border bg-paper/80 px-3 py-1">{{ $content['reading_time'] }}</span>
                    <span class="rounded-full border border-border bg-paper/80 px-3 py-1">
                        {{ $content['updated_label'] }}: {{ $manifest['updated_at'] ?? '' }}
                    </span>
                </div>
            </div>
            <div class="min-w-0 lg:col-span-5">
                <figure class="overflow-hidden rounded-3xl border border-border bg-paper shadow-lift">
                    <img
                        src="{{ asset($diagrams['hero'] ?? 'images/learn/road-signs-hero.svg') }}"
                        alt="{{ $content['visuals']['alts']['hero'] ?? '' }}"
                        width="720"
                        height="360"
                        class="h-auto w-full object-contain"
                        decoding="async"
                        fetchpriority="high"
                    />
                    <figcaption class="border-t border-border px-4 py-3 text-sm leading-relaxed text-navy/75">
                        {{ $content['visuals']['captions']['hero'] ?? '' }}
                    </figcaption>
                </figure>
            </div>
        </div>
    </section>

    <div class="section-pad section-paper" data-educational-guide data-road-signs-library>
        <div class="section-shell space-y-12 lg:space-y-14">
            @if (! empty($content['draft_notice']) || $usingFallback)
                <p class="rounded-2xl border border-amber/40 bg-amber-soft px-4 py-3 text-sm leading-relaxed text-navy" role="status">
                    {{ $content['draft_notice'] ?: __('pages.traffic_rules.empty_body') }}
                </p>
            @endif

            {{-- Intro + TOC --}}
            <div class="grid items-start gap-8 lg:grid-cols-12 lg:gap-10">
                <div class="min-w-0 space-y-6 lg:col-span-8">
                    <p class="text-base leading-relaxed text-navy/80 sm:text-lg" id="intro">
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
                </div>

                <aside class="min-w-0 space-y-4 lg:col-span-4 lg:self-start" aria-label="{{ __('pages.learn.breadcrumb') }}">
                    <p class="text-sm font-semibold uppercase tracking-wide text-navy/70">{{ __('pages.learn.breadcrumb') }}</p>
                    <x-ui.resource-card
                        :title="__('pages.learn.card_rules_title')"
                        :description="__('pages.learn.card_rules_desc')"
                        :href="route('learn.traffic-rules')"
                        icon="book"
                        accent="blue"
                        :equal-height="false"
                    />
                    <x-ui.resource-card
                        :title="__('pages.learn.card_hazard_title')"
                        :description="__('pages.learn.card_hazard_desc')"
                        :href="route('learn.hazard-prediction')"
                        icon="alert"
                        accent="teal"
                        :equal-height="false"
                    />
                </aside>
            </div>

            @if ($showMidAd)
                <x-ui.ad-slot name="content-mid" class="mx-0" />
            @endif

            {{-- 2. Category explanation --}}
            <section id="categories" class="scroll-mt-24 space-y-5" data-road-signs-categories>
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-navy sm:text-[1.75rem]">{{ $content['categories_heading'] }}</h2>
                    <p class="mt-3 max-w-3xl text-base leading-relaxed text-navy/80">{{ $content['categories_intro'] }}</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($categoryKeys as $categoryKey)
                        @php
                            $category = $content['categories'][$categoryKey] ?? [];
                            $categoryCardClass = match ($categoryKey) {
                                'regulatory' => 'learn-card--vermilion',
                                'warning' => 'learn-card--gold',
                                'instruction' => 'learn-card--indigo',
                                'guide' => 'learn-card--indigo',
                                'supplementary' => 'learn-card--matcha',
                                default => 'border-border bg-white',
                            };
                        @endphp
                        <article class="rounded-3xl border p-5 shadow-soft {{ $categoryCardClass }}">
                            <p class="text-xs font-semibold uppercase tracking-wide text-accent">
                                {{ data_get($manifest, "categories.{$categoryKey}.official_ja") }}
                            </p>
                            <h3 class="mt-2 text-lg font-bold text-navy">{{ $category['title'] ?? '' }}</h3>
                            <p class="mt-2 text-sm font-medium text-navy/90">{{ $category['lead'] ?? '' }}</p>
                            <p class="mt-2 text-sm leading-relaxed text-navy/75">{{ $category['body'] ?? '' }}</p>
                        </article>
                    @endforeach
                </div>
                <aside class="rounded-3xl border border-matcha/30 bg-matcha/10 p-5">
                    <h3 class="text-base font-bold text-navy">{{ $content['supplementary_note']['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-navy/80">{{ $content['supplementary_note']['body'] }}</p>
                </aside>
            </section>

            {{-- 3–4. Filters + library (full-width; no reveal animation on tall content) --}}
            <section
                id="library"
                class="scroll-mt-24 space-y-5"
                data-sign-library
                data-road-signs-layout="full"
                x-data="{
                    category: @js($activeCategory),
                    query: @js($searchQuery),
                    matches(el) {
                        const cat = el.dataset.category;
                        const hay = (el.dataset.search || '').toLowerCase();
                        const q = (this.query || '').toLowerCase().trim();
                        const catOk = this.category === 'all' || cat === this.category;
                        const qOk = !q || hay.includes(q);
                        return catOk && qOk;
                    },
                    get visibleCount() {
                        return [...this.$refs.grid.querySelectorAll('[data-sign-filter-item]')].filter((el) => this.matches(el)).length;
                    },
                    collapseHidden() {
                        [...this.$refs.grid.querySelectorAll('[data-sign-filter-item]')].forEach((el) => {
                            if (this.matches(el)) {
                                return;
                            }
                            const details = el.querySelector('details');
                            if (details) {
                                details.open = false;
                            }
                        });
                    },
                    init() {
                        this.$watch('category', () => this.$nextTick(() => this.collapseHidden()));
                        this.$watch('query', () => this.$nextTick(() => this.collapseHidden()));
                    }
                }"
            >
                <div class="rounded-3xl border border-accent/20 bg-gradient-to-br from-washi via-paper to-white p-5 shadow-soft sm:p-6" data-sign-filters>
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-navy sm:text-[1.75rem]">{{ $content['library_heading'] }}</h2>
                        <p class="mt-3 max-w-3xl text-base leading-relaxed text-navy/80">{{ $content['library_lead'] }}</p>
                    </div>

                    <form method="get" action="{{ route('learn.road-signs') }}" class="mt-5 space-y-4" role="search">
                        <div>
                            <label for="road-sign-search" class="text-sm font-semibold text-navy">{{ $content['search_label'] }}</label>
                            <input
                                id="road-sign-search"
                                type="search"
                                name="q"
                                value="{{ $searchQuery }}"
                                placeholder="{{ $content['search_placeholder'] }}"
                                class="mt-2 w-full rounded-2xl border border-border bg-white px-4 py-3 text-base text-navy shadow-soft placeholder:text-navy/45 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30"
                                x-model="query"
                                autocomplete="off"
                            />
                        </div>
                        <div>
                            <p id="filter-label" class="text-sm font-semibold text-navy">{{ $content['filter_label'] }}</p>
                            <div class="mt-3 flex flex-wrap gap-2" role="group" aria-labelledby="filter-label">
                                @foreach ($filters as $key => $label)
                                    <button
                                        type="submit"
                                        name="category"
                                        value="{{ $key }}"
                                        class="min-h-11 rounded-full border border-border bg-white px-4 py-2 text-sm font-semibold text-navy transition hover:border-accent/40 hover:bg-accent/5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent {{ $filterChipClass[$key] ?? '' }}{{ $activeCategory === $key ? ' filter-chip-active' : '' }}"
                                        :class="category === @js($key) ? 'filter-chip-active' : ''"
                                        :data-active="category === @js($key) ? 'true' : 'false'"
                                        @click="category = @js($key); $nextTick(() => collapseHidden())"
                                        @if ($activeCategory === $key) aria-current="true" @endif
                                    >
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <p class="text-sm font-medium text-navy/70" aria-live="polite">
                            <span x-text="visibleCount"></span> / {{ count($allSigns) }}
                        </p>
                        <noscript>
                            <button type="submit" class="btn-secondary min-h-11">{{ $content['filter_label'] }}</button>
                        </noscript>
                    </form>
                </div>

                <div
                    class="grid grid-cols-1 items-start gap-4 sm:grid-cols-2 xl:grid-cols-3"
                    data-sign-grid
                    data-sign-details
                    x-ref="grid"
                >
                    @foreach ($allSigns as $sign)
                        @php
                            $hiddenByServerFilter = $hasServerFilter && ! in_array($sign['id'], $filteredSignIds, true);
                        @endphp
                        <div
                            class="min-w-0"
                            data-sign-filter-item
                            data-category="{{ $sign['category'] }}"
                            data-search="{{ e(mb_strtolower(implode(' ', [
                                $sign['official_name_ja'],
                                $sign['title'],
                                $sign['meaning_short'],
                                $sign['category_label'],
                                implode(' ', $sign['keywords']),
                            ]))) }}"
                            @if ($hiddenByServerFilter) style="display: none" @endif
                            x-show="matches($el)"
                            x-transition.opacity.duration.150ms
                        >
                            <x-learn.road-sign-card
                                :sign="$sign"
                                :labels="$signCardLabels"
                                :sources="$sourcesById"
                            />
                        </div>
                    @endforeach
                </div>

                <p
                    class="rounded-2xl border border-border bg-surface px-4 py-3 text-sm text-navy/80 sm:text-base"
                    x-show="visibleCount === 0"
                    @unless ($hasServerFilter && $signs === [])
                        x-cloak
                    @endunless
                    role="status"
                >
                    {{ $content['empty_state'] }}
                </p>

                <div class="flex flex-col gap-3 rounded-3xl border border-accent/25 bg-gradient-to-r from-accent/10 via-washi to-gold/15 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-accent">{{ $content['practice']['badge'] }}</p>
                        <p class="mt-1 text-base font-bold text-navy sm:text-lg">{{ $content['library_practice_cta'] }}</p>
                    </div>
                    <a href="#practice" class="btn-primary inline-flex min-h-12 shrink-0 items-center justify-center px-5">
                        {{ $content['library_practice_button'] }}
                    </a>
                </div>
            </section>

            @if ($showInlineAd)
                <x-ui.ad-slot name="article-inline" class="mx-0" />
            @endif

            {{-- 5. Driving scenarios — two columns on desktop --}}
            <section id="scenes" class="scroll-mt-24 space-y-5" data-road-sign-scenes>
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-navy sm:text-[1.75rem]">{{ $content['scenes_heading'] }}</h2>
                    <p class="mt-3 max-w-3xl text-base leading-relaxed text-navy/80">{{ $content['scenes_lead'] }}</p>
                </div>
                <div class="grid gap-6 md:grid-cols-2">
                    @foreach (['stop', 'no_entry', 'speed', 'parking', 'crossing', 'warning'] as $sceneKey)
                        @php $scene = $content['scenes'][$sceneKey] ?? []; @endphp
                        <article class="flex h-full flex-col rounded-3xl border border-border bg-white p-5 shadow-soft sm:p-6">
                            <h3 class="text-lg font-bold text-navy">{{ $scene['title'] ?? '' }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-navy/80 sm:text-base">{{ $scene['body'] ?? '' }}</p>
                            <x-learn.guide-figure
                                :asset-key="$sceneKey"
                                :assets="$assets"
                                :captions="$content['visuals']['captions'] ?? []"
                                :alts="$content['visuals']['alts'] ?? []"
                                class="mt-4 grow"
                            />
                        </article>
                    @endforeach
                </div>
            </section>

            {{-- 6. Recognition practice — prominent panel --}}
            <section
                id="practice"
                class="scroll-mt-24 space-y-5 rounded-[1.75rem] border border-accent/30 bg-gradient-to-br from-accent/10 via-washi to-gold/10 p-5 shadow-soft sm:p-8"
                data-educational-practice
                data-sign-recognition-practice
                x-data="{
                    items: @js($practicePayload),
                    index: 0,
                    selected: null,
                    revealed: false,
                    finished: false,
                    get item() { return this.finished ? null : (this.items[this.index] || null) },
                    get total() { return this.items.length },
                    select(i) {
                        if (this.revealed || this.finished) return;
                        this.selected = i;
                        this.revealed = true;
                    },
                    next() {
                        if (this.index < this.total - 1) {
                            this.index++;
                            this.selected = null;
                            this.revealed = false;
                            return;
                        }
                        this.finished = true;
                        this.revealed = false;
                        this.selected = null;
                    },
                    restart() {
                        this.index = 0;
                        this.selected = null;
                        this.revealed = false;
                        this.finished = false;
                    }
                }"
            >
                <div class="max-w-3xl">
                    <p class="text-sm font-semibold tracking-wide text-accent">{{ $content['practice']['badge'] }}</p>
                    <h2 class="mt-2 text-2xl font-bold tracking-tight text-navy sm:text-3xl">{{ $content['practice']['title'] }}</h2>
                    <p class="mt-3 text-base leading-relaxed text-navy/80 sm:text-lg">{{ $content['practice']['lead'] }}</p>
                </div>

                <div class="rounded-3xl border border-accent/25 bg-white p-5 shadow-lift sm:p-8" x-show="item" x-cloak>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="rounded-full border border-accent/20 bg-accent/10 px-3 py-1 text-sm font-semibold text-accent-dark">
                            {{ $content['practice']['progress'] }}:
                            <span x-text="(index + 1) + ' / ' + total"></span>
                        </p>
                        <p class="rounded-full bg-washi px-3 py-1 text-sm font-semibold text-navy" x-text="item?.official_name_ja"></p>
                    </div>

                    <div class="mt-6 grid items-start gap-6 xl:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)]" x-transition>
                        <div class="road-sign-card__art flex min-w-0 justify-center rounded-3xl border border-gold/20 p-6 sm:p-8">
                            <img
                                :src="item?.illustration || ''"
                                :alt="item?.alt || ''"
                                width="260"
                                height="260"
                                class="h-44 w-44 object-contain sm:h-56 sm:w-56"
                            />
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-lg font-bold leading-snug text-navy sm:text-xl" x-text="item?.q"></h3>
                            <ul class="mt-5 space-y-3">
                                <template x-for="(choice, choiceIndex) in (item?.choices || [])" :key="choiceIndex">
                                    <li class="min-w-0">
                                        <button
                                            type="button"
                                            class="w-full min-h-12 whitespace-normal break-words rounded-2xl border px-4 py-3.5 text-left text-base leading-relaxed transition duration-200"
                                            :class="{
                                                'border-matcha bg-matcha/20 font-semibold text-navy ring-2 ring-matcha/30': revealed && choiceIndex === item.answer,
                                                'border-vermilion bg-vermilion/15 font-semibold text-navy': revealed && selected === choiceIndex && choiceIndex !== item.answer,
                                                'border-border bg-surface text-navy hover:border-accent/50 hover:bg-accent/5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent': !revealed || (selected !== choiceIndex && choiceIndex !== item.answer)
                                            }"
                                            @click="select(choiceIndex)"
                                            :disabled="revealed"
                                            x-text="choice"
                                        ></button>
                                    </li>
                                </template>
                            </ul>
                            <div x-show="revealed" x-transition.opacity.duration.200ms class="mt-5 space-y-2 rounded-2xl border border-border bg-washi/80 px-4 py-3 text-base leading-relaxed text-navy/85">
                                <p
                                    class="font-bold"
                                    :class="selected === item.answer ? 'text-matcha' : 'text-vermilion'"
                                    x-text="selected === item.answer ? @js($content['practice']['correct']) : @js($content['practice']['incorrect'])"
                                ></p>
                                <p><span class="font-semibold text-navy">{{ $content['practice']['explain_label'] }}:</span> <span x-text="item?.explain"></span></p>
                            </div>
                            <div class="mt-6 flex flex-wrap gap-3">
                                <button type="button" class="btn-primary min-h-12" x-show="revealed" @click="next()">
                                    <span x-text="index < total - 1 ? @js($content['practice']['next']) : @js($content['practice']['finish'])"></span>
                                </button>
                                <button type="button" class="btn-secondary min-h-12" @click="restart()">
                                    {{ $content['practice']['restart'] }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-3xl border border-matcha/30 bg-gradient-to-br from-matcha/15 via-white to-gold/10 p-6 shadow-soft sm:p-8"
                    x-show="finished"
                    x-cloak
                    role="status"
                >
                    <h3 class="text-xl font-bold text-navy sm:text-2xl">{{ $content['practice']['complete_title'] }}</h3>
                    <p class="mt-3 max-w-2xl text-base leading-relaxed text-navy/80">{{ $content['practice']['complete_body'] }}</p>
                    <button type="button" class="btn-primary mt-6 min-h-12" @click="restart()">
                        {{ $content['practice']['restart'] }}
                    </button>
                </div>

                <noscript>
                    <p class="mb-4 text-sm text-navy/80">{{ $content['practice']['noscript_note'] }}</p>
                    <div class="space-y-4">
                        @foreach ($practiceItems as $index => $item)
                            @php
                                $sign = collect($allSigns)->firstWhere('id', $item['sign_id'] ?? '');
                            @endphp
                            <article class="rounded-3xl border border-border bg-white p-5 shadow-soft">
                                <h3 class="text-base font-semibold text-navy">Q{{ $index + 1 }}. {{ $item['q'] }}</h3>
                                @if ($sign)
                                    <img src="{{ asset($sign['illustration']) }}" alt="{{ $sign['alt'] }}" width="140" height="140" class="mt-3 h-28 w-28 object-contain" loading="lazy">
                                @endif
                                <ul class="mt-3 space-y-2">
                                    @foreach ($item['choices'] ?? [] as $choice)
                                        <li class="rounded-2xl border border-border bg-surface px-4 py-3 text-sm text-navy/80">{{ $choice }}</li>
                                    @endforeach
                                </ul>
                                <p class="mt-3 text-sm leading-relaxed text-navy/80">
                                    <span class="font-semibold text-navy">{{ $content['practice']['correct'] }}:</span>
                                    {{ $item['choices'][$item['answer'] ?? 0] ?? '' }} — {{ $item['explain'] }}
                                </p>
                            </article>
                        @endforeach
                    </div>
                </noscript>
            </section>

            {{-- 7. Common mistakes --}}
            <section id="mistakes" class="scroll-mt-24 space-y-4">
                <h2 class="text-2xl font-bold tracking-tight text-navy sm:text-[1.75rem]">{{ $content['mistakes_heading'] }}</h2>
                <div class="grid gap-3 md:grid-cols-2">
                    @foreach ($content['mistakes'] ?? [] as $mistake)
                        <article class="rounded-3xl border border-border bg-white p-5 shadow-soft">
                            <p class="text-sm font-semibold text-vermilion sm:text-base">× {{ $mistake['wrong'] }}</p>
                            <p class="mt-2 text-sm leading-relaxed text-navy/80 sm:text-base">○ {{ $mistake['right'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>

            {{-- 8. FAQ --}}
            <section id="faq" class="scroll-mt-24 space-y-4">
                <h2 class="text-2xl font-bold tracking-tight text-navy sm:text-[1.75rem]">{{ $content['faq_heading'] }}</h2>
                <div class="space-y-3">
                    @foreach ($content['faq'] ?? [] as $faq)
                        <details class="group rounded-3xl border border-border bg-white p-5 shadow-soft open:border-accent/30 open:shadow-lift">
                            <summary class="cursor-pointer list-none text-base font-semibold leading-snug text-navy marker:content-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent [&::-webkit-details-marker]:hidden sm:text-lg">
                                <span class="flex items-start justify-between gap-3">
                                    <span>{{ $faq['q'] }}</span>
                                    <span class="text-accent transition group-open:rotate-45 motion-reduce:transition-none" aria-hidden="true">+</span>
                                </span>
                            </summary>
                            <p class="mt-3 text-base leading-relaxed text-navy/85 sm:text-[1.05rem]">{{ $faq['a'] }}</p>
                        </details>
                    @endforeach
                </div>
            </section>

            {{-- Sources --}}
            <section id="sources" class="scroll-mt-24 space-y-4" data-guide-sources>
                <h2 class="text-2xl font-bold tracking-tight text-navy sm:text-[1.75rem]">{{ $content['sources_heading'] }}</h2>
                <p class="max-w-3xl text-base leading-relaxed text-navy/80">{{ $content['sources_intro'] }}</p>
                <ul class="grid gap-4 md:grid-cols-2">
                    @foreach ($sources as $source)
                        <li class="rounded-3xl border border-border bg-paper p-5 shadow-soft">
                            <p class="font-semibold text-navy">{{ $source['title'] }}</p>
                            <p class="mt-1 text-sm text-navy/75">{{ $content['source_org'] }}: {{ $source['organization'] }}</p>
                            <p class="mt-1 text-sm text-navy/75">{{ $content['source_section'] }}: {{ $source['section'] }}</p>
                            <p class="mt-1 text-sm text-navy/75">{{ $content['source_checked'] }}: {{ $source['checked_at'] }}</p>
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

            {{-- 9. Related study resources --}}
            <section id="related" class="scroll-mt-24 space-y-4">
                <h2 class="text-2xl font-bold tracking-tight text-navy sm:text-[1.75rem]">{{ $content['related_heading'] }}</h2>
                <p class="max-w-3xl text-base leading-relaxed text-navy/80">{{ $content['related_intro'] }}</p>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <a href="{{ route('learn.traffic-rules.pedestrian-priority') }}" class="learn-card--matcha rounded-2xl border p-4 shadow-soft transition hover:border-matcha/50 hover:shadow-lift">
                        <img src="{{ asset(config('content.pedestrian_priority.thumbnail')) }}" alt="" width="320" height="160" class="mb-3 h-28 w-full rounded-xl bg-washi object-contain" loading="lazy" decoding="async">
                        <p class="font-semibold text-navy">{{ $content['related']['pedestrian']['title'] }}</p>
                        <p class="mt-1 text-sm leading-relaxed text-navy/75">{{ $content['related']['pedestrian']['desc'] }}</p>
                    </a>
                    <a href="{{ route('learn.traffic-rules.intersection-priority') }}" class="learn-card--gold rounded-2xl border p-4 shadow-soft transition hover:border-gold/50 hover:shadow-lift">
                        <img src="{{ asset(config('content.intersection_priority.thumbnail')) }}" alt="" width="320" height="160" class="mb-3 h-28 w-full rounded-xl bg-washi object-contain" loading="lazy" decoding="async">
                        <p class="font-semibold text-navy">{{ $content['related']['intersection']['title'] }}</p>
                        <p class="mt-1 text-sm leading-relaxed text-navy/75">{{ $content['related']['intersection']['desc'] }}</p>
                    </a>
                    <a href="{{ route('learn.traffic-rules') }}" class="learn-card--indigo rounded-2xl border p-4 shadow-soft transition hover:border-accent/50 hover:shadow-lift">
                        <p class="font-semibold text-navy">{{ $content['related']['rules']['title'] }}</p>
                        <p class="mt-1 text-sm leading-relaxed text-navy/75">{{ $content['related']['rules']['desc'] }}</p>
                    </a>
                    <a href="{{ route('exams.karimen') }}" class="learn-card--gold rounded-2xl border p-4 shadow-soft transition hover:border-gold/50 hover:shadow-lift">
                        <p class="font-semibold text-navy">{{ $content['related']['karimen']['title'] }}</p>
                        <p class="mt-1 text-sm leading-relaxed text-navy/75">{{ $content['related']['karimen']['desc'] }}</p>
                    </a>
                    <a href="{{ route('exams.honmen') }}" class="learn-card--indigo rounded-2xl border p-4 shadow-soft transition hover:border-accent/50 hover:shadow-lift sm:col-span-2 xl:col-span-1">
                        <p class="font-semibold text-navy">{{ $content['related']['honmen']['title'] }}</p>
                        <p class="mt-1 text-sm leading-relaxed text-navy/75">{{ $content['related']['honmen']['desc'] }}</p>
                    </a>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    <a href="{{ route('exams.index') }}" class="btn-gold inline-flex min-h-12 w-full items-center justify-center sm:w-auto">
                        {{ $content['cta_practice'] }}
                    </a>
                    <a href="{{ route('exams.karimen') }}" class="btn-secondary inline-flex min-h-12 w-full items-center justify-center sm:w-auto">
                        {{ $content['cta_karimen'] }}
                    </a>
                    <a href="{{ route('exams.honmen') }}" class="btn-secondary inline-flex min-h-12 w-full items-center justify-center sm:w-auto">
                        {{ $content['cta_honmen'] }}
                    </a>
                </div>
            </section>

            @if ($showBottomAd)
                <x-ui.ad-slot name="content-bottom" />
            @endif

            <x-ui.disclaimer-notice class="p-4 sm:p-5" />
        </div>
    </div>
</x-layouts.site>
