@php
    $navLinks = [
        ['label' => __('navigation.home'), 'route' => 'home', 'active' => 'home'],
        ['label' => __('navigation.exams'), 'route' => 'exams.index', 'active' => 'exams.*'],
        ['label' => __('navigation.learn'), 'route' => 'learn.index', 'active' => 'learn.*'],
        ['label' => __('navigation.road_signs'), 'route' => 'learn.road-signs', 'active' => 'learn.road-signs'],
        ['label' => __('navigation.guides'), 'route' => 'guides.index', 'active' => 'guides.*'],
        ['label' => __('navigation.faq'), 'route' => 'faq', 'active' => 'faq'],
        ['label' => __('navigation.about'), 'route' => 'about', 'active' => 'about'],
    ];

    $isNavActive = function (array $link): bool {
        $pattern = $link['active'] ?? $link['route'];

        return request()->routeIs($pattern);
    };
@endphp

<header
    class="site-header sticky top-0 z-50 border-b border-border bg-surface/95 backdrop-blur supports-[backdrop-filter]:bg-surface/90"
    data-site-header
    x-data="{ open: false }"
    x-init="$watch('open', value => document.body.classList.toggle('nav-open', !!value))"
    @keydown.escape.window="open = false"
>
    <div class="section-shell flex h-16 items-center justify-between gap-3 sm:gap-4">
        <a
            href="{{ route('home') }}"
            class="site-brand shrink-0 rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
            data-site-logo
        >
            <x-logo variant="full" class="h-10 w-auto max-w-[12.75rem] sm:h-11 sm:max-w-[15.75rem]" />
        </a>

        <nav class="hidden items-center gap-1 lg:flex" aria-label="{{ __('navigation.primary') }}">
            @foreach ($navLinks as $link)
                <a
                    href="{{ route($link['route']) }}"
                    @class([
                        'nav-link',
                        'is-active' => $isNavActive($link),
                    ])
                >
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="hidden items-center gap-3 lg:flex">
            <x-site.language-switcher />

            @auth
                <a
                    href="{{ route('dashboard') }}"
                    class="inline-flex items-center justify-center rounded-xl bg-accent px-4 py-2 text-sm font-semibold text-white shadow-soft transition hover:bg-accent-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                >
                    {{ __('navigation.dashboard') }}
                </a>
            @else
                <a
                    href="{{ route('login') }}"
                    class="rounded-lg px-3 py-2 text-sm font-medium text-navy-muted transition hover:text-navy focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                >
                    {{ __('navigation.login') }}
                </a>
                <a
                    href="{{ route('register') }}"
                    class="inline-flex items-center justify-center rounded-xl border border-gold/40 bg-gradient-to-b from-gold-bright to-gold px-4 py-2 text-sm font-semibold text-navy shadow-soft transition hover:shadow-lift focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                >
                    {{ __('navigation.register') }}
                </a>
            @endauth
        </div>

        <button
            type="button"
            class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg p-2 text-navy transition lg:hidden focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
            :aria-expanded="open.toString()"
            aria-controls="mobile-menu"
            @click="open = ! open"
        >
            <span class="sr-only">{{ __('navigation.open_menu') }}</span>
            <svg x-show="! open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <svg x-show="open" x-cloak class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <x-site.mobile-menu :links="$navLinks" />
</header>
