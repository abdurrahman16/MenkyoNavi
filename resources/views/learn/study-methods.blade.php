@php
    use App\Support\Advertising;

    /** @var array<string, mixed> $content */
    /** @var array<string, mixed> $manifest */
    /** @var list<array<string, mixed>> $sources */
    $showMidAd = Advertising::shouldRenderSlot('content-mid');
    $showBottomAd = Advertising::shouldRenderSlot('content-bottom');
    $toc = $content['toc'] ?? [];
@endphp

<x-layouts.site
    :title="$content['meta_title']"
    :description="$content['meta_description']"
    :canonical="route('learn.study-methods')"
    :robots="$robots"
    :json-ld="$jsonLd"
>
    <x-ui.breadcrumb :items="[
        ['label' => __('pages.home'), 'url' => route('home')],
        ['label' => __('pages.learn.breadcrumb'), 'url' => route('learn.index')],
        ['label' => $content['breadcrumb'], 'url' => route('learn.study-methods')],
    ]" />

    <section class="relative overflow-hidden border-b border-border gradient-hero py-10 sm:py-12" data-study-methods-hero>
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
                    <x-ui.human-image
                        image-key="learner_studying_desk"
                        :alt="$content['image_alts']['hero'] ?? ''"
                        :priority="true"
                        sizes="(max-width: 1024px) 100vw, 480px"
                        class="aspect-[16/9]"
                    />
                    <figcaption class="border-t border-border px-4 py-3 text-sm text-navy-muted">
                        {{ $content['image_captions']['hero'] ?? '' }}
                    </figcaption>
                </figure>
            </div>
        </div>
    </section>

    <div class="section-pad section-paper">
        <div class="section-shell grid items-start gap-8 lg:grid-cols-12 lg:gap-10">
            <article class="min-w-0 space-y-10 lg:col-span-8" data-study-methods-guide>
                @if (! empty($content['draft_notice']) || $usingFallback)
                    <p class="rounded-2xl border border-amber/40 bg-amber-soft px-4 py-3 text-sm leading-relaxed text-navy" role="status">
                        {{ $content['draft_notice'] ?: __('pages.traffic_rules.empty_body') }}
                    </p>
                @endif

                <p class="text-base leading-relaxed text-navy-muted sm:text-lg">{{ $content['intro'] }}</p>
                <p class="rounded-2xl border border-gold/30 bg-gold/10 px-4 py-3 text-sm leading-relaxed text-navy">{{ $content['disclaimer'] }}</p>

                <nav class="rounded-3xl border border-navy/15 bg-gradient-to-br from-washi via-paper to-surface p-5 shadow-soft sm:p-6" aria-label="{{ $content['toc_label'] }}">
                    <h2 class="text-lg font-semibold text-navy">{{ $content['toc_label'] }}</h2>
                    <ol class="mt-4 grid gap-2 sm:grid-cols-2">
                        @foreach ($toc as $anchor => $label)
                            <li>
                                <a href="#{{ $anchor }}" class="text-sm font-medium text-accent hover:text-accent-dark">{{ $label }}</a>
                            </li>
                        @endforeach
                    </ol>
                </nav>

                @foreach ($content['sections'] ?? [] as $key => $section)
                    <section id="{{ $key }}" class="scroll-mt-24 space-y-3" data-study-section="{{ $key }}">
                        <h2 class="text-2xl font-bold text-navy">{{ $section['title'] }}</h2>
                        <p class="leading-relaxed text-navy-muted">{{ $section['body'] }}</p>
                        @if ($key === 'routine')
                            <figure class="overflow-hidden rounded-3xl border border-border bg-paper shadow-soft">
                                <x-ui.human-image
                                    image-key="learner_driving_instruction"
                                    :alt="$content['image_alts']['instruction'] ?? ''"
                                    sizes="(max-width: 1024px) 100vw, 720px"
                                />
                                <figcaption class="border-t border-border px-4 py-3 text-sm text-navy-muted">
                                    {{ $content['image_captions']['instruction'] ?? '' }}
                                </figcaption>
                            </figure>
                        @endif
                    </section>

                    @if ($showMidAd && $key === 'true_false')
                        <x-ui.ad-slot name="content-mid" />
                    @endif
                @endforeach

                <section id="plans" class="scroll-mt-24 space-y-6" data-study-plans
                    x-data="{
                        storageKey: 'menkyo-study-plan-checks-v1',
                        checks: {},
                        init() {
                            try {
                                const raw = window.localStorage.getItem(this.storageKey);
                                this.checks = raw ? JSON.parse(raw) : {};
                            } catch (e) {
                                this.checks = {};
                            }
                        },
                        toggle(id) {
                            this.checks[id] = ! this.checks[id];
                            try {
                                window.localStorage.setItem(this.storageKey, JSON.stringify(this.checks));
                            } catch (e) {}
                        }
                    }"
                >
                    <h2 class="text-2xl font-bold text-navy">{{ $content['plans']['title'] }}</h2>
                    <p class="leading-relaxed text-navy-muted">{{ $content['plans']['lead'] }}</p>
                    <p class="text-sm leading-relaxed text-navy-muted">{{ $content['plans']['local_only_note'] }}</p>

                    <figure class="overflow-hidden rounded-3xl border border-border bg-paper shadow-soft">
                        <x-ui.human-image
                            image-key="focused_study_session"
                            :alt="$content['image_alts']['plans'] ?? ''"
                            sizes="(max-width: 1024px) 100vw, 720px"
                        />
                        <figcaption class="border-t border-border px-4 py-3 text-sm text-navy-muted">
                            {{ $content['image_captions']['plans'] ?? '' }}
                        </figcaption>
                    </figure>

                    @foreach (['seven', 'fourteen'] as $planKey)
                        @php $plan = $content['plans'][$planKey] ?? []; @endphp
                        <div class="rounded-3xl border border-border bg-white p-5 shadow-soft sm:p-6" data-plan="{{ $planKey }}">
                            <h3 class="text-xl font-bold text-navy">{{ $plan['title'] ?? '' }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-navy-muted">{{ $plan['audience'] ?? '' }}</p>
                            <ul class="mt-5 space-y-3">
                                @foreach ($plan['days'] ?? [] as $day)
                                    @php
                                        $checkId = $planKey.'-day-'.$day['day'];
                                        $href = $day['href'] ?? null;
                                        $url = is_string($href) && \Illuminate\Support\Facades\Route::has($href) ? route($href) : route('learn.index');
                                    @endphp
                                    <li class="rounded-2xl border border-border/80 bg-washi/40 p-4">
                                        <label class="flex cursor-pointer items-start gap-3">
                                            <input
                                                type="checkbox"
                                                class="mt-1 h-4 w-4 rounded border-border text-accent focus:ring-accent"
                                                :checked="!!checks['{{ $checkId }}']"
                                                @change="toggle('{{ $checkId }}')"
                                            >
                                            <span class="min-w-0">
                                                <span class="block text-sm font-semibold text-navy">
                                                    Day {{ $day['day'] }} — {{ $day['title'] }}
                                                </span>
                                                <span class="mt-1 block text-sm leading-relaxed text-navy-muted">{{ $day['body'] }}</span>
                                                <a href="{{ $url }}" class="mt-2 inline-flex text-sm font-semibold text-accent hover:text-accent-dark">
                                                    {{ __('pages.learn.open_resource') }}
                                                </a>
                                            </span>
                                        </label>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </section>

                <section id="tips" class="scroll-mt-24 space-y-4" data-study-tips>
                    <h2 class="text-2xl font-bold text-navy">{{ $content['tips']['title'] }}</h2>
                    <p class="leading-relaxed text-navy-muted">{{ $content['tips']['lead'] }}</p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($content['tips']['items'] ?? [] as $tip)
                            <article class="rounded-3xl border border-matcha/25 bg-gradient-to-br from-matcha/10 via-paper to-paper p-5 shadow-soft">
                                <h3 class="text-base font-semibold text-navy">{{ $tip['title'] }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-navy-muted">{{ $tip['body'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section id="sources" class="scroll-mt-24 space-y-4">
                    <h2 class="text-2xl font-bold text-navy">{{ $content['sources_heading'] }}</h2>
                    <ul class="space-y-4">
                        @foreach ($sources as $source)
                            <li class="rounded-3xl border border-border bg-paper p-5 shadow-soft">
                                <p class="font-semibold text-navy">{{ $source['title'] }}</p>
                                <p class="mt-1 text-sm text-navy-muted">{{ $source['organization'] }}</p>
                                @if (! empty($source['section']))
                                    <p class="mt-1 text-sm text-navy-muted">{{ $source['section'] }}</p>
                                @endif
                                @if (! empty($source['url']))
                                    <a href="{{ $source['url'] }}" class="mt-3 inline-flex text-sm font-semibold text-accent hover:text-accent-dark" rel="noopener noreferrer" target="_blank">
                                        {{ $source['url'] }}
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>

                <section id="related" class="scroll-mt-24 space-y-4">
                    <h2 class="text-2xl font-bold text-navy">{{ $content['related_heading'] }}</h2>
                    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                        <a href="{{ route('learn.index') }}" class="btn-secondary">{{ $content['related']['learn'] }}</a>
                        <a href="{{ route('exams.index') }}" class="btn-primary">{{ $content['related']['practice'] }}</a>
                        <a href="{{ route('learn.road-signs') }}" class="btn-vermilion">{{ $content['related']['signs'] }}</a>
                        <a href="{{ route('learn.traffic-rules.pedestrian-priority') }}" class="btn-matcha">{{ $content['related']['pedestrian'] }}</a>
                        <a href="{{ route('learn.traffic-rules.intersection-priority') }}" class="btn-secondary">{{ $content['related']['intersection'] }}</a>
                    </div>
                </section>

                <section class="overflow-hidden rounded-3xl border border-accent/25 bg-gradient-to-br from-accent/10 via-paper to-washi p-6 shadow-soft sm:p-8">
                    <div class="grid items-center gap-6 lg:grid-cols-2">
                        <div>
                            <h2 class="text-2xl font-bold text-navy">{{ $content['cta_title'] }}</h2>
                            <p class="mt-3 text-sm leading-relaxed text-navy-muted sm:text-base">{{ $content['cta_body'] }}</p>
                            <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                                <a href="{{ route('exams.index') }}" class="btn-primary">{{ $content['cta_practice'] }}</a>
                                <a href="{{ route('learn.index') }}" class="btn-secondary">{{ $content['cta_learn'] }}</a>
                            </div>
                        </div>
                        <figure class="overflow-hidden rounded-3xl border border-border bg-paper">
                            <x-ui.human-image
                                image-key="exam_confidence_review"
                                :alt="$content['image_alts']['cta'] ?? ''"
                                sizes="(max-width: 1024px) 100vw, 420px"
                            />
                            <figcaption class="border-t border-border px-4 py-3 text-sm text-navy-muted">
                                {{ $content['image_captions']['cta'] ?? '' }}
                            </figcaption>
                        </figure>
                    </div>
                </section>

                @if ($showBottomAd)
                    <x-ui.ad-slot name="content-bottom" />
                @endif

                <x-ui.disclaimer-notice />
            </article>

            <aside class="min-w-0 space-y-5 lg:col-span-4 lg:sticky lg:top-24 lg:self-start">
                <div class="rounded-3xl border border-border bg-white p-5 shadow-soft">
                    <h2 class="text-lg font-semibold text-navy">{{ __('pages.learn.journey_title') }}</h2>
                    <ol class="mt-4 space-y-3 text-sm text-navy-muted">
                        <li>{{ __('pages.learn.journey_step_learn') }}</li>
                        <li>{{ __('pages.learn.journey_step_practice') }}</li>
                        <li>{{ __('pages.learn.journey_step_review') }}</li>
                        <li>{{ __('pages.learn.journey_step_improve') }}</li>
                        <li>{{ __('pages.learn.journey_step_repeat') }}</li>
                    </ol>
                    <a href="{{ route('exams.index') }}" class="btn-primary mt-5 w-full">{{ __('pages.learn.practice_cta') }}</a>
                </div>
            </aside>
        </div>
    </div>
</x-layouts.site>
