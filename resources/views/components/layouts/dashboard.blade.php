@props([
    'title' => config('app.name') . ' | Dashboard',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <x-seo.meta :title="$title" robots="noindex, follow" />

    <x-seo.favicon />

    <style>[x-cloak]{display:none!important}</style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-surface font-sans text-ink antialiased">
    <a
        href="#main-content"
        class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[100] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:font-semibold focus:text-navy focus:shadow-soft focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
    >
        {{ __('common.skip_to_content') }}
    </a>

    <header class="sticky top-0 z-50 border-b border-border bg-paper/95 backdrop-blur supports-[backdrop-filter]:bg-paper/90">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <a
                href="{{ route('home') }}"
                class="rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
            >
                <x-logo variant="full" class="h-10 w-auto max-w-[14rem]" />
            </a>

            <nav class="hidden items-center gap-1 md:flex" aria-label="Dashboard">
                @php
                    $dashLinks = [
                        ['label' => __('navigation.dashboard'), 'route' => 'dashboard'],
                        ['label' => __('navigation.exams'), 'route' => 'exams.index'],
                        ['label' => __('navigation.learn'), 'route' => 'learn.index'],
                        ['label' => __('account.profile'), 'route' => 'profile.edit'],
                    ];
                @endphp
                @foreach ($dashLinks as $link)
                    <a
                        href="{{ route($link['route']) }}"
                        @class([
                            'rounded-lg px-3 py-2 text-sm font-medium transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent',
                            'bg-accent/10 text-accent' => request()->routeIs($link['route']),
                            'text-navy-muted hover:text-navy' => ! request()->routeIs($link['route']),
                        ])
                    >
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="flex items-center gap-3">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="rounded-lg px-3 py-2 text-sm font-medium text-navy-muted transition hover:text-navy focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                    >
                        {{ __('account.logout') }}
                    </button>
                </form>
            </div>
        </div>
    </header>

    @isset($header)
        <div class="border-b border-border bg-white">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </div>
    @endisset

    <main id="main-content">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
