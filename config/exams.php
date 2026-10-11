<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configurable mock-exam formats
    |--------------------------------------------------------------------------
    |
    | These values seed exam_types and should be verified against current
    | official licensing rules before public release. They are intentionally
    | stored as configuration + database rows, not hardcoded in controllers.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Demo practice
    |--------------------------------------------------------------------------
    |
    | Null keeps demo practice available in local and testing, and off everywhere
    | else. Set EXAM_DEMO_ENABLED=true only as an explicit development override.
    | Demo sessions use unpublished sample questions and are not official exams.
    |
    */

    'demo_enabled' => env('EXAM_DEMO_ENABLED'),

    'types' => [
        'gaimen-kirikae' => [
            'name' => 'Gaimen Kirikae',
            'name_ja' => '外免切替',
            'description' => 'Foreign license conversion written practice format (configurable mock settings).',
            'duration_minutes' => 30,
            'max_score' => 50,
            'passing_score' => 45,
            'selection_rules' => [
                'standard_true_false_count' => 50,
                'illustrated_group_count' => 0,
                'points_per_standard_question' => 1,
                'points_per_illustrated_group' => 0,
                'answer_style' => 'true_false',
                'requires_official_verification' => true,
            ],
        ],
        'karimen' => [
            'name' => 'Karimen',
            'name_ja' => '仮免',
            'description' => 'Provisional / learner permit written practice format (configurable mock settings).',
            'duration_minutes' => 30,
            'max_score' => 50,
            'passing_score' => 45,
            'selection_rules' => [
                'standard_true_false_count' => 50,
                'illustrated_group_count' => 0,
                'points_per_standard_question' => 1,
                'points_per_illustrated_group' => 0,
                'answer_style' => 'true_false',
                'requires_official_verification' => true,
            ],
        ],
        'honmen' => [
            'name' => 'Honmen',
            'name_ja' => '本免',
            'description' => 'Final written practice format with standard items plus illustrated groups (configurable mock settings).',
            'duration_minutes' => 50,
            'max_score' => 100,
            'passing_score' => 90,
            'selection_rules' => [
                'standard_true_false_count' => 90,
                'illustrated_group_count' => 5,
                'points_per_standard_question' => 1,
                'points_per_illustrated_group' => 2,
                'statements_per_illustrated_group' => 3,
                'illustrated_scoring' => 'all_or_nothing',
                'answer_style' => 'true_false',
                'requires_official_verification' => true,
            ],
        ],
    ],

];
