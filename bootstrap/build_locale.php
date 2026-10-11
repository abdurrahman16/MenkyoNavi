<?php

declare(strict_types=1);

require __DIR__.'/export_array.php';

$locale = $argv[1] ?? null;
if ($locale === null) {
    fwrite(STDERR, "Usage: php bootstrap/build_locale.php <locale>\n");
    exit(1);
}

$draftNotices = [
    'ko' => '이 번역은 원어민 검토를 기다리는 초안입니다.',
    'zh' => '本译文为草稿，尚待母语使用者审阅。',
    'vi' => 'Bản dịch này là bản nháp, đang chờ người bản ngữ rà soát.',
    'fil' => 'Ang salin na ito ay draft at naghihintay ng pagsusuri mula sa isang katutubong tagapagsalita.',
    'ne' => 'यो अनुवाद मस्यौदा हो र मातृभाषी समीक्षाको प्रतीक्षामा छ।',
    'bn' => 'এই অনুবাদটি খসড়া; মাতৃভাষী পর্যালোচকারির অপেক্ষায় রয়েছে।',
];

$titles = [
    'ko' => ['일본 도로표지 배우기 | MenkyoNavi', '일본 도로표지 배우기'],
    'zh' => ['学习日本道路标志 | MenkyoNavi', '学习日本道路标志'],
    'vi' => ['Học biển báo đường bộ Nhật Bản | MenkyoNavi', 'Học biển báo đường bộ Nhật Bản'],
    'fil' => ['Alamin ang mga karatula sa kalsada sa Japan | MenkyoNavi', 'Alamin ang mga karatula sa kalsada sa Japan'],
    'ne' => ['जापानी सडक चिन्ह सिक्नुहोस् | MenkyoNavi', 'जापानी सडक चिन्ह सिक्नुहोस्'],
    'bn' => ['জাপানি সড়ক চিহ্ন শিখুন | MenkyoNavi', 'জাপানি সড়ক চিহ্ন শিখুন'],
];

$corePath = __DIR__."/_{$locale}_ui_map_core.php";
$signPath = __DIR__."/_{$locale}_sign_text.php";
if (! is_file($corePath) || ! is_file($signPath)) {
    fwrite(STDERR, "Missing core or sign file for {$locale}\n");
    exit(1);
}

$en = require dirname(__DIR__).'/lang/en/road_signs.php';
/** @var array<string, string> $map */
$map = require $corePath;
/** @var array<string, array<string, mixed>> $signText */
$signText = require $signPath;

foreach ($en['signs'] as $id => $fields) {
    if (! isset($signText[$id])) {
        continue;
    }
    foreach ($fields as $key => $enVal) {
        if ($key === 'keywords') {
            foreach ($enVal as $i => $enKw) {
                if (isset($signText[$id]['keywords'][$i])) {
                    $map[$enKw] = $signText[$id]['keywords'][$i];
                }
            }

            continue;
        }
        if (is_string($enVal) && isset($signText[$id][$key])) {
            $map[$enVal] = $signText[$id][$key];
        }
    }
}

$data = translateTree($en, $map);
$data['draft_notice'] = $draftNotices[$locale];
[$metaTitle, $title] = $titles[$locale];
$data['meta_title'] = $metaTitle;
$data['title'] = $title;

file_put_contents(dirname(__DIR__)."/lang/{$locale}/road_signs.php", "<?php\n\nreturn ".exportArray($data).";\n");
echo "Built {$locale}\n";

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
