@props([
    'title' => config('app.name'),
    'description' => null,
    'canonical' => null,
    'robots' => null,
    'ogImage' => null,
    'jsonLd' => null,
])

@php
    $canonical = $canonical ?? url()->current();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" {{ $attributes }}>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <x-seo.meta
        :title="$title"
        :description="$description"
        :canonical="$canonical"
        :robots="$robots"
        :og-image="$ogImage"
    />

    @if ($jsonLd)
        <x-seo.json-ld :data="$jsonLd" />
    @endif

    <x-seo.favicon />
    <meta name="theme-color" content="#26466D">

    @if (! request()->routeIs('exams.attempts.*', 'login', 'register', 'password.*', 'verification.*', 'admin.*', 'dashboard', 'dashboard.*', 'profile.*'))
        <meta name="view-transition" content="same-origin">
    @endif

    <style>
        [x-cloak]{display:none!important}
        .reveal,.motion-reveal-up,.motion-reveal-left,.motion-reveal-right,.motion-reveal-scale,.motion-reveal-mask{opacity:1;transform:none;clip-path:none}
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-ads.adsense-script />
    @livewireStyles
</head>
<body class="page-enter min-h-screen bg-surface font-sans text-ink antialiased">
    <a
        href="#main-content"
        class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[100] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:font-semibold focus:text-navy focus:shadow-soft focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
    >
        {{ __('common.skip_to_content') }}
    </a>

    <x-site.header />

    <main id="main-content">
        {{ $slot }}
    </main>

    <x-site.footer />

    @livewireScripts
</body>
</html>
