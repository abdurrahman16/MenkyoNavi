<?php

declare(strict_types=1);

require __DIR__.'/export_array.php';

$locales = [
    'vi' => [
        'notice' => 'Bản dịch này là bản nháp, đang chờ người bản ngữ rà soát.',
        'meta' => 'Học biển báo đường bộ Nhật Bản | MenkyoNavi',
        'title' => 'Học biển báo đường bộ Nhật Bản',
        'map' => [
            '日本の道路標識を学ぶ | MenkyoNavi' => 'Học biển báo đường bộ Nhật Bản | MenkyoNavi',
            '日本の道路標識を学ぶ' => 'Học biển báo đường bộ Nhật Bản',
            '道路標識' => 'Biển báo giao thông',
            '交通ルール学習ガイド' => 'Hướng dẫn học luật giao thông',
            '目次' => 'Mục lục',
            'よくある質問' => 'Câu hỏi thường gặp',
        ],
    ],
    'fil' => [
        'notice' => 'Ang salin na ito ay draft at naghihintay ng pagsusuri mula sa isang katutubong tagapagsalita.',
        'meta' => 'Alamin ang mga karatula sa kalsada sa Japan | MenkyoNavi',
        'title' => 'Alamin ang mga karatula sa kalsada sa Japan',
        'map' => [
            '日本の道路標識を学ぶ | MenkyoNavi' => 'Alamin ang mga karatula sa kalsada sa Japan | MenkyoNavi',
            '日本の道路標識を学ぶ' => 'Alamin ang mga karatula sa kalsada sa Japan',
            '道路標識' => 'Mga karatula sa kalsada',
            '交通ルール学習ガイド' => 'Gabay sa pag-aaral ng trapiko',
            '目次' => 'Talaan',
            'よくある質問' => 'Mga madalas itanong',
        ],
    ],
    'ne' => [
        'notice' => 'यो अनुवाद मस्यौदा हो र मातृभाषी समीक्षाको प्रतीक्षामा छ।',
        'meta' => 'जापानी सडक चिन्ह सिक्नुहोस् | MenkyoNavi',
        'title' => 'जापानी सडक चिन्ह सिक्नुहोस्',
        'map' => [
            '日本の道路標識を学ぶ | MenkyoNavi' => 'जापानी सडक चिन्ह सिक्नुहोस् | MenkyoNavi',
            '日本の道路標識を学ぶ' => 'जापानी सडक चिन्ह सिक्नुहोस्',
            '道路標識' => 'सडक चिन्ह',
            '交通ルール学習ガイド' => 'यातायात नियम अध्ययन गाइड',
            '目次' => 'विषयसूची',
            'よくある質問' => 'बारम्बार सोधिने प्रश्न',
        ],
    ],
    'bn' => [
        'notice' => 'এই অনুবাদটি খসড়া; মাতৃভাষী পর্যালোচকারির অপেক্ষায় রয়েছে।',
        'meta' => 'জাপানি সড়ক চিহ্ন শিখুন | MenkyoNavi',
        'title' => 'জাপানি সড়ক চিহ্ন শিখুন',
        'map' => [
            '日本の道路標識を学ぶ | MenkyoNavi' => 'জাপানি সড়ক চিহ্ন শিখুন | MenkyoNavi',
            '日本の道路標識を学ぶ' => 'জাপানি সড়ক চিহ্ন শিখুন',
            '道路標識' => 'সড়ক চিহ্ন',
            '交通ルール学習ガイド' => 'যানবাহন নিয়ম শেখার গাইড',
            '目次' => 'সূচিপত্র',
            'よくある質問' => 'প্রায়শই জিজ্ঞাসা',
        ],
    ],
];

$ja = require dirname(__DIR__).'/lang/ja/road_signs.php';

foreach ($locales as $locale => $cfg) {
    $data = translateTree($ja, $cfg['map']);
    $data['draft_notice'] = $cfg['notice'];
    $data['meta_title'] = $cfg['meta'];
    $data['title'] = $cfg['title'];
    file_put_contents(dirname(__DIR__)."/lang/{$locale}/road_signs.php", "<?php\n\nreturn ".exportArray($data).";\n");
    echo "Built {$locale} from ja\n";
}

/** @param array<string, mixed> $node @param array<string, string> $map */
function translateTree(array $node, array $map): array
{
    $out = [];
    foreach ($node as $key => $value) {
        if (is_array($value)) {
            $out[$key] = translateTree($value, $map);
        } elseif (is_string($value)) {
            $out[$key] = $map[$value] ?? $value;
        } else {
            $out[$key] = $value;
        }
    }

    return $out;
}
