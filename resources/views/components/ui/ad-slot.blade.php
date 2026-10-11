@props([
    'name' => 'content-mid',
    'context' => null,
])

@php
    use App\Support\Advertising;

    $shouldRender = Advertising::shouldRenderSlot($name, $context);
    $layoutPreview = Advertising::layoutPreviewActive();
    $slotConfig = config("ads.slots.{$name}");
    $label = is_array($slotConfig) ? ($slotConfig['label'] ?? __('common.advertisement')) : __('common.advertisement');
    $minHeight = (int) (is_array($slotConfig) ? ($slotConfig['min_height'] ?? 90) : 90);
    $unitId = Advertising::slotUnitId($name);
    $clientId = Advertising::clientId();
@endphp

@if ($shouldRender)
    <aside
        {{ $attributes->merge(['class' => 'ad-slot w-full']) }}
        aria-label="{{ $label }}"
        data-ad-placement="{{ $name }}"
        @if ($layoutPreview) data-ad-preview="true" @endif
    >
        <p class="mb-2 text-center text-xs font-medium uppercase tracking-wide text-navy-muted">
            {{ $layoutPreview ? __('common.layout_preview_ad') : $label }}
        </p>
        <div
            class="flex w-full items-center justify-center overflow-hidden rounded-xl border border-dashed border-border bg-paper/80 text-sm text-navy-muted"
            style="min-height: {{ $minHeight }}px;"
        >
            @if ($layoutPreview)
                <span class="pointer-events-none select-none px-3 text-center text-xs leading-relaxed text-navy-muted">
                    {{ __('common.reserved_ad_space') }} ({{ $name }})
                </span>
            @elseif ($unitId && $clientId)
                <ins
                    class="adsbygoogle"
                    style="display:block;width:100%;min-height:{{ $minHeight }}px;"
                    data-ad-client="{{ $clientId }}"
                    data-ad-slot="{{ $unitId }}"
                    data-ad-format="auto"
                    data-full-width-responsive="true"
                ></ins>
                <script>
                    (window.adsbygoogle = window.adsbygoogle || []).push({});
                </script>
            @endif
        </div>
    </aside>
@endif
