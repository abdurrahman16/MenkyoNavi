<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google AdSense readiness
    |--------------------------------------------------------------------------
    |
    | Advertising is disabled by default. Do not set a fake publisher ID.
    | No third-party ad scripts load until enabled is true AND a real
    | publisher ID is set AND (when required) consent has been granted.
    |
    */

    'enabled' => (bool) env('ADSENSE_ENABLED', false),

    /*
    | Publisher / client ID. Accepts pub-XXXXXXXXXXXXXXXX or ca-pub-XXXXXXXXXXXXXXXX.
    | Leave empty until AdSense approval. Never invent a placeholder ID.
    |
    | A valid publisher ID alone enables dynamic /ads.txt for site verification.
    | It does not load AdSense scripts until enabled is true and consent gates pass.
    */
    'publisher_id' => env('ADSENSE_PUBLISHER_ID', env('ADSENSE_CLIENT_ID')),

    'auto_enabled' => (bool) env('ADSENSE_AUTO_ENABLED', true),

    'manual_enabled' => (bool) env('ADSENSE_MANUAL_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Local layout preview (non-production)
    |--------------------------------------------------------------------------
    |
    | When true in a non-production environment, ad regions may show clearly
    | labeled, non-clickable layout placeholders. Never loads AdSense scripts.
    |
    */

    'layout_preview' => (bool) env('ADS_LAYOUT_PREVIEW', false),

    /*
    |--------------------------------------------------------------------------
    | Consent (future Google-certified CMP integration)
    |--------------------------------------------------------------------------
    |
    | When require_consent is true, live ad slots/scripts stay inactive until
    | advertising consent is marked granted (e.g. via a real CMP callback).
    | Do not treat a custom dialog as Google-certified CMP.
    |
    */

    'require_consent' => (bool) env('ADSENSE_REQUIRE_CONSENT', true),

    'consent_granted' => (bool) env('ADSENSE_CONSENT_GRANTED', false),

    /*
    |--------------------------------------------------------------------------
    | Routes excluded from Auto ads / advertising scripts
    |--------------------------------------------------------------------------
    */

    'excluded_route_patterns' => [
        'login',
        'register',
        'password.*',
        'verification.*',
        'admin.*',
        'dashboard',
        'dashboard.*',
        'profile.*',
        'contact',
        'contact.store',
        'exams.attempts.*',
        'privacy-policy',
        'terms-of-service',
        'disclaimer',
        'cookie-policy',
        'advertising-disclosure',
        'mistakes.*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Independent placements (manual units)
    |--------------------------------------------------------------------------
    |
    | Manual units render only when advertising is live, manual_enabled is true,
    | and a numeric ad_unit_id is configured. Auto ads do not require these slots.
    | Exam-adjacent slots stay disabled until verified safe.
    |
    */

    'slots' => [
        'content-mid' => [
            'enabled' => true,
            'label' => 'Advertisement',
            'min_height' => 90,
            'formats' => ['horizontal', 'responsive'],
            'ad_unit_id' => env('ADSENSE_SLOT_CONTENT_MID'),
        ],
        'content-bottom' => [
            'enabled' => true,
            'label' => 'Advertisement',
            'min_height' => 90,
            'formats' => ['horizontal', 'responsive'],
            'ad_unit_id' => env('ADSENSE_SLOT_CONTENT_BOTTOM'),
        ],
        'sidebar' => [
            'enabled' => true,
            'label' => 'Advertisement',
            'min_height' => 250,
            'formats' => ['vertical', 'rectangle'],
            'ad_unit_id' => env('ADSENSE_SLOT_SIDEBAR'),
        ],
        'article-inline' => [
            'enabled' => true,
            'label' => 'Advertisement',
            'min_height' => 90,
            'formats' => ['horizontal', 'responsive'],
            'ad_unit_id' => env('ADSENSE_SLOT_ARTICLE_INLINE'),
        ],
        'exam-left' => [
            'enabled' => false,
            'label' => 'Advertisement',
            'min_height' => 250,
            'formats' => ['vertical', 'rectangle'],
            'ad_unit_id' => env('ADSENSE_SLOT_EXAM_LEFT'),
        ],
        'exam-right' => [
            'enabled' => false,
            'label' => 'Advertisement',
            'min_height' => 250,
            'formats' => ['vertical', 'rectangle'],
            'ad_unit_id' => env('ADSENSE_SLOT_EXAM_RIGHT'),
        ],
        'exam-bottom' => [
            'enabled' => false,
            'label' => 'Advertisement',
            'min_height' => 90,
            'formats' => ['horizontal', 'responsive'],
            'ad_unit_id' => env('ADSENSE_SLOT_EXAM_BOTTOM'),
        ],
    ],

    'allowed_slots' => [
        'content-mid',
        'content-bottom',
        'sidebar',
        'article-inline',
        'exam-left',
        'exam-right',
        'exam-bottom',
    ],

    'forbidden_contexts' => [
        'exam_active',
        'auth',
        'dashboard',
        'account',
        'contact',
    ],

];
