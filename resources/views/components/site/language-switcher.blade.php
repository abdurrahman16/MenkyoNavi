@php
    $locales = config('menkyo.locales', []);
    $current = app()->getLocale();
    $aliases = config('menkyo.locale_aliases', []);
    $current = $aliases[$current] ?? $current;
    if (! array_key_exists($current, $locales)) {
        $current = config('menkyo.default_locale', 'ja');
    }
@endphp

<div class="relative">
    <form id="locale-switcher-form" method="POST" action="{{ route('locale.switch', $current) }}">
        @csrf
        <label for="site-language" class="sr-only">{{ __('common.language') }}</label>
        <select
            id="site-language"
            name="locale"
            class="min-h-10 rounded-xl border border-border bg-white py-2 pl-3 pr-8 text-sm text-navy shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
            onchange="this.form.action = this.options[this.selectedIndex].dataset.action; this.form.submit();"
        >
            @foreach ($locales as $code => $meta)
                @php
                    $label = is_array($meta) ? ($meta['native'] ?? $meta['label'] ?? $code) : $meta;
                @endphp
                <option
                    value="{{ $code }}"
                    data-action="{{ route('locale.switch', $code) }}"
                    @selected($current === $code)
                >
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </form>
</div>
