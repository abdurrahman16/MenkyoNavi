@php
    use App\Support\Advertising;

    $shouldLoad = Advertising::shouldLoadAutoAdsScript();
    $clientId = Advertising::clientId();
@endphp

@if ($shouldLoad && $clientId)
    <script
        async
        src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ urlencode($clientId) }}"
        crossorigin="anonymous"
        data-adsense-auto="true"
    ></script>
@endif
