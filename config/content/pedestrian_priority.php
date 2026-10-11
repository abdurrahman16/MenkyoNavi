<?php

/**
 * Pedestrian priority educational guide — verification & publication manifest.
 *
 * Separate from exam question data. Legal claims in the public guide must be
 * backed by sources listed here. Translation status is independent of
 * whether a locale file exists.
 */
return [
    'slug' => 'pedestrian-priority',
    'route' => 'learn.traffic-rules.pedestrian-priority',
    'path' => '/traffic-rules/pedestrian-priority',
    'published_at' => '2026-10-10',
    'updated_at' => '2026-10-10',
    'status' => 'published',
    'authoritative_locale' => 'ja',
    'sitemap' => true,
    'ads_eligible' => true,

    /*
    | Locale publication status for this guide.
    | - authored: written in that language for MenkyoNavi
    | - draft_translation: complete draft, not native-speaker reviewed
    | - pending: intentionally withheld from public indexing
    */
    'locales' => [
        'ja' => [
            'status' => 'authored',
            'review' => 'facts_checked_against_sources',
            'indexable' => true,
            'native_review' => false,
        ],
        'en' => [
            'status' => 'draft_translation',
            'review' => 'pending_native_review',
            'indexable' => false,
            'native_review' => false,
        ],
        'zh' => [
            'status' => 'draft_translation',
            'review' => 'pending_native_review',
            'indexable' => false,
            'native_review' => false,
        ],
        'vi' => [
            'status' => 'draft_translation',
            'review' => 'pending_native_review',
            'indexable' => false,
            'native_review' => false,
        ],
        'fil' => [
            'status' => 'draft_translation',
            'review' => 'pending_native_review',
            'indexable' => false,
            'native_review' => false,
        ],
        'ne' => [
            'status' => 'draft_translation',
            'review' => 'pending_native_review',
            'indexable' => false,
            'native_review' => false,
        ],
        'bn' => [
            'status' => 'draft_translation',
            'review' => 'pending_native_review',
            'indexable' => false,
            'native_review' => false,
        ],
        'ko' => [
            'status' => 'draft_translation',
            'review' => 'pending_native_review',
            'indexable' => false,
            'native_review' => false,
        ],
    ],

    'sources' => [
        [
            'id' => 'npa-crosswalk-2026',
            'title' => '横断歩道は歩行者優先です',
            'organization' => '警察庁',
            'url' => 'https://www.npa.go.jp/bureau/traffic/oudanhodou/info.html',
            'section' => '横断歩道付近等における交通ルール / 道路交通法の条文（第38条・第38条の2・第12条・第13条） / 罰則等',
            'checked_at' => '2026-10-10',
            'supports' => [
                'Drivers must approach crosswalks at a stoppable speed unless it is clear no pedestrian/cyclist will cross.',
                'Drivers must stop before the crosswalk (or stop line) when a pedestrian or cyclist is crossing or about to cross, and must not impede them.',
                'Drivers must stop before passing a vehicle stopped at a crosswalk.',
                'No overtaking within 30 m before a crosswalk (with statutory exceptions).',
                'At intersections without a crosswalk, drivers must not impede pedestrians who are crossing.',
                'Pedestrian crossing-method duties under Articles 12 and 13 (summarized educationally).',
            ],
        ],
        [
            'id' => 'egov-road-traffic-act',
            'title' => '道路交通法',
            'organization' => 'e-Gov 法令検索（デジタル庁）',
            'url' => 'https://laws.e-gov.go.jp/law/335AC0000000105',
            'section' => '第2条（横断歩道の定義）、第12条、第13条、第38条、第38条の2',
            'checked_at' => '2026-10-10',
            'supports' => [
                'Statutory wording of pedestrian priority at crosswalks (Art. 38).',
                'Pedestrian priority near intersections without crosswalks (Art. 38-2).',
                'Legal definition of a crosswalk.',
            ],
        ],
        [
            'id' => 'ibaraki-police-crosswalk',
            'title' => '横断歩道における交通事故防止',
            'organization' => '茨城県警察',
            'url' => 'https://www.pref.ibaraki.jp/kenkei/a02_traffic/jikoboushi/odanhodo.html',
            'section' => '横断歩道等における歩行者等優先（第38条第1項の解説）',
            'checked_at' => '2026-10-10',
            'supports' => [
                'Plain-language explanation of the approach-speed duty and stop duty under Art. 38(1).',
            ],
        ],
    ],

    'thumbnail' => 'images/learn/pedestrian-priority-thumb.svg',

    'diagrams' => [
        'approach' => 'images/learn/pedestrian-priority-approach.svg',
        'sightlines' => 'images/learn/pedestrian-priority-sightlines.svg',
        'preparing' => 'images/learn/pedestrian-priority-preparing.svg',
        'intersection' => 'images/learn/pedestrian-priority-intersection.svg',
    ],

    'assets' => [
        'thumbnail' => [
            'path' => 'images/learn/pedestrian-priority-thumb.svg',
            'origin' => 'project-created',
            'license' => 'original-menkyo-navi',
            'section' => 'related',
            'width' => 480,
            'height' => 240,
        ],
        'approach' => [
            'path' => 'images/learn/pedestrian-priority-approach.svg',
            'origin' => 'project-created',
            'license' => 'original-menkyo-navi',
            'section' => 'hero',
            'width' => 640,
            'height' => 320,
        ],
        'sightlines' => [
            'path' => 'images/learn/pedestrian-priority-sightlines.svg',
            'origin' => 'project-created',
            'license' => 'original-menkyo-navi',
            'section' => 'visuals',
            'width' => 640,
            'height' => 320,
        ],
        'preparing' => [
            'path' => 'images/learn/pedestrian-priority-preparing.svg',
            'origin' => 'project-created',
            'license' => 'original-menkyo-navi',
            'section' => 'preparing',
            'width' => 640,
            'height' => 320,
        ],
        'intersection' => [
            'path' => 'images/learn/pedestrian-priority-intersection.svg',
            'origin' => 'project-created',
            'license' => 'original-menkyo-navi',
            'section' => 'visuals',
            'width' => 640,
            'height' => 320,
        ],
    ],
];
