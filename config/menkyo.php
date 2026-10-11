<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Brand
    |--------------------------------------------------------------------------
    */

    'name' => 'MenkyoNavi',
    'name_ja' => '免許ナビ',
    'tagline' => 'Your Guide to Passing the Japanese Driving License Exam.',
    'logo' => '/images/logo.png',
    'icon' => '/images/fav.png',

    'exam_labels' => [
        'gaimen' => '外免切替',
        'karimen' => '仮免',
        'honmen' => '本免',
    ],

    /*
    |--------------------------------------------------------------------------
    | Contact
    |--------------------------------------------------------------------------
    |
    | Server-only recipient for Contact Us form delivery. Never expose this value
    | in Blade, JSON-LD, public JavaScript, or mailto links.
    | Do not invent a company name, street address, or telephone number.
    |
    */

    'contact_recipient_email' => env(
        'CONTACT_RECIPIENT_EMAIL',
        env('MENKYO_CONTACT_EMAIL', env('CONTACT_EMAIL'))
    ),

    /*
    |--------------------------------------------------------------------------
    | Public registration
    |--------------------------------------------------------------------------
    |
    | Leave REGISTRATION_ENABLED unset for automatic behavior:
    | - local/testing: open (log mailer OK for developers)
    | - other environments: open only when MAIL_MAILER delivers externally
    | Set true/false to force open/closed regardless of mailer.
    |
    */

    'registration_enabled' => env('REGISTRATION_ENABLED'),

    /*
    | @deprecated Use contact_recipient_email. Kept as an alias for internal mail
    | routing only — never pass this into public views.
    */
    'contact_email' => env(
        'CONTACT_RECIPIENT_EMAIL',
        env('MENKYO_CONTACT_EMAIL', env('CONTACT_EMAIL'))
    ),

    'operating_location' => [
        'en' => 'Tokyo, Japan',
        'ja' => '日本・東京都',
    ],

    /*
    |--------------------------------------------------------------------------
    | Locales
    |--------------------------------------------------------------------------
    |
    | Japanese is the default interface language for first-time visitors.
    | All eight locales are enabled for a complete single-language UI.
    | Do not emit hreflang for locale-prefixed URL variants until those
    | routes and reviewed page translations exist (future SEO task).
    |
    */

    'default_locale' => env('APP_LOCALE', 'ja'),

    'locales' => [
        'ja' => ['label' => 'Japanese', 'native' => '日本語', 'enabled' => true],
        'en' => ['label' => 'English', 'native' => 'English', 'enabled' => true],
        'zh' => ['label' => 'Chinese (Simplified)', 'native' => '简体中文', 'enabled' => true],
        'vi' => ['label' => 'Vietnamese', 'native' => 'Tiếng Việt', 'enabled' => true],
        'fil' => ['label' => 'Filipino', 'native' => 'Filipino', 'enabled' => true],
        'ne' => ['label' => 'Nepali', 'native' => 'नेपाली', 'enabled' => true],
        'bn' => ['label' => 'Bengali', 'native' => 'বাংলা', 'enabled' => true],
        'ko' => ['label' => 'Korean', 'native' => '한국어', 'enabled' => true],
    ],

    /*
    | Legacy Tagalog code mapped to canonical Filipino (fil).
    */
    'locale_aliases' => [
        'tl' => 'fil',
    ],

    /*
    |--------------------------------------------------------------------------
    | Technical SEO
    |--------------------------------------------------------------------------
    |
    | allow_indexing: null follows APP_ENV production. Set false on any publicly
    | reachable staging host. google_site_verification is optional HTML-tag
    | ownership proof — never invent a token.
    |
    */

    'seo' => [
        'allow_indexing' => env('MENKYO_ALLOW_INDEXING'),
        'google_site_verification' => env('GOOGLE_SITE_VERIFICATION'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Public sitemap eligibility
    |--------------------------------------------------------------------------
    |
    | Only indexable public educational / hub pages. Privacy and terms remain
    | publicly linked with noindex and are intentionally omitted here.
    | Locale variants share these URLs (session/cookie language) — do not invent
    | alternate language paths in the sitemap.
    |
    */

    'sitemap_routes' => [
        'home',
        'exams.index',
        'exams.gaimen-kirikae',
        'exams.karimen',
        'exams.honmen',
        'learn.index',
        'learn.study-methods',
        'learn.road-signs',
        'learn.traffic-rules',
        'learn.traffic-rules.pedestrian-priority',
        'learn.traffic-rules.intersection-priority',
        'learn.hazard-prediction',
        'guides.index',
        'guides.foreign-license-conversion',
        'guides.required-documents',
        'guides.exam-centers',
        'about',
        'contact',
        'faq',
        'editorial-policy',
        'sources',
        'disclaimer',
        'cookie-policy',
        'advertising-disclosure',
    ],

];
