@props([
    'title' => config('app.name'),
    'description' => null,
    'canonical' => null,
    'robots' => null,
    'ogImage' => null,
])

@php
    use App\Support\Seo\CanonicalUrl;
    use App\Support\Seo\IndexingPolicy;

    $canonical = CanonicalUrl::from($canonical ?? CanonicalUrl::current());
    $description = $description ?: 'MenkyoNavi (免許ナビ) — independent educational resources for Japan driving license written exams.';
    $ogImage = $ogImage ?: asset('/images/og-default.svg');
    $robots = IndexingPolicy::resolveRobots($robots);
    $googleSiteVerification = IndexingPolicy::googleSiteVerificationToken();
@endphp

<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">
<link rel="canonical" href="{{ $canonical }}">
<meta name="robots" content="{{ $robots }}">
@if ($googleSiteVerification)
    <meta name="google-site-verification" content="{{ $googleSiteVerification }}">
@endif

<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:type" content="website">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $ogImage }}">
