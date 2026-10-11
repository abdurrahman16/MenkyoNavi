@props([
    'links' => [],
])

<div
    id="mobile-menu"
    x-show="open"
    x-cloak
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 -translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 -translate-y-2"
    class="border-t border-border bg-paper lg:hidden"
    role="dialog"
    aria-modal="true"
    aria-label="Mobile navigation"
    @click.outside="open = false"
>
    <nav class="mx-auto max-w-7xl space-y-1 px-4 py-4 sm:px-6" aria-label="Mobile primary">
        @foreach ($links as $link)
            @php
                $active = request()->routeIs($link['active'] ?? $link['route']);
            @endphp
            <a
                href="{{ route($link['route']) }}"
                @click="open = false"
                @class([
                    'block rounded-lg px-3 py-3 text-base font-semibold text-accent bg-accent/10' => $active,
                    'block rounded-lg px-3 py-3 text-base font-medium text-navy-muted hover:bg-accent/5 hover:text-navy' => ! $active,
                ])
            >
                {{ $link['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="border-t border-border px-4 py-4 sm:px-6">
        <x-site.language-switcher />
    </div>

    <div class="flex flex-col gap-3 border-t border-border px-4 py-4 sm:px-6">
        @auth
            <a
                href="{{ route('dashboard') }}"
                @click="open = false"
                class="inline-flex items-center justify-center rounded-xl bg-accent px-4 py-3 text-sm font-semibold text-white shadow-soft"
            >
                {{ __('navigation.dashboard') }}
            </a>
        @else
            <a
                href="{{ route('login') }}"
                @click="open = false"
                class="inline-flex min-h-12 items-center justify-center rounded-xl border border-border bg-white px-4 py-3 text-sm font-semibold text-navy"
            >
                {{ __('navigation.login') }}
            </a>
            <a
                href="{{ route('register') }}"
                @click="open = false"
                class="inline-flex min-h-12 items-center justify-center rounded-xl bg-accent px-4 py-3 text-sm font-semibold text-white shadow-soft"
            >
                {{ __('navigation.register') }}
            </a>
        @endauth
    </div>
</div>
