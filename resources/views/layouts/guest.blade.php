<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex, nofollow">

        <title>{{ config('app.name', 'MenkyoNavi') }}</title>

        <x-seo.favicon />

        <style>[x-cloak]{display:none!important}</style>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-navy antialiased">
        <div class="flex min-h-screen flex-col items-center bg-surface pt-6 sm:justify-center sm:pt-0">
            <div>
                <a href="{{ route('home') }}" class="inline-block rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">
                    <x-logo class="h-10 w-auto max-w-[14rem]" />
                </a>
            </div>

            <div class="mt-6 w-full overflow-hidden rounded-2xl border border-border bg-white px-6 py-6 shadow-soft sm:max-w-md">
                {{ $slot }}
            </div>

            <p class="mt-6 px-4 text-center text-xs text-navy-muted">
                Independent educational platform — not an official government service.
            </p>
        </div>
    </body>
</html>
