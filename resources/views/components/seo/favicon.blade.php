{{-- Cache-bust when icon cropping changes; browsers pin favicons aggressively. --}}
@php($faviconVersion = '29')
<link rel="icon" href="{{ asset('images/favicon-32.png') }}?v={{ $faviconVersion }}" type="image/png" sizes="32x32">
<link rel="icon" href="{{ asset('images/favicon-16.png') }}?v={{ $faviconVersion }}" type="image/png" sizes="16x16">
<link rel="icon" href="{{ asset('favicon.svg') }}?v={{ $faviconVersion }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}?v={{ $faviconVersion }}">
<link rel="icon" href="{{ asset('images/fav.png') }}?v={{ $faviconVersion }}" type="image/png" sizes="any">
