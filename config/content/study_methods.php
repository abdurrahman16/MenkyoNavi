<?php

/**
 * Study methods educational guide — publication and indexing manifest.
 *
 * Original preparation guidance for MenkyoNavi learners. Not official exam
 * requirements. Locale drafts remain non-indexable until native review.
 */
return [
    'slug' => 'study-methods',
    'route' => 'learn.study-methods',
    'path' => '/learn/study-methods',
    'published_at' => '2026-10-10',
    'updated_at' => '2026-10-10',
    'status' => 'published',
    'authoritative_locale' => 'ja',
    'sitemap' => true,
    'ads_eligible' => true,

    'locales' => [
        'ja' => [
            'status' => 'authored',
            'review' => 'editorial_original',
            'indexable' => true,
            'native_review' => true,
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
            'id' => 'npa-traffic-safety',
            'title' => '交通安全教育・運転免許に関する情報',
            'organization' => '警察庁',
            'url' => 'https://www.npa.go.jp/bureau/traffic/',
            'section' => '交通局（運転免許・交通安全）',
            'checked_at' => '2026-10-10',
            'supports' => [
                'Points learners to official Japanese traffic-safety and licensing information channels.',
            ],
        ],
        [
            'id' => 'egov-road-traffic-act',
            'title' => '道路交通法',
            'organization' => 'e-Gov 法令検索',
            'url' => 'https://laws.e-gov.go.jp/law/335AC0000000105',
            'section' => '道路交通法（全文）',
            'checked_at' => '2026-10-10',
            'supports' => [
                'Statutory reference for learning traffic rules rather than memorizing quiz answers.',
            ],
        ],
        [
            'id' => 'menkyo-navi-editorial',
            'title' => 'MenkyoNavi editorial study guidance',
            'organization' => 'MenkyoNavi',
            'url' => null,
            'section' => 'Original preparation methods for practice exams and guides',
            'checked_at' => '2026-10-10',
            'supports' => [
                'Practical study routines, mistake review habits, and exam-day preparation tips.',
            ],
        ],
    ],

    'thumbnail' => 'images/humans/focused-study-session.webp',

    'images' => [
        'hero' => 'learner_studying_desk',
        'instruction' => 'learner_driving_instruction',
        'plans' => 'focused_study_session',
        'cta' => 'exam_confidence_review',
    ],
];
