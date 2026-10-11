<?php

declare(strict_types=1);

/**
 * Builds non-ja/en locale files from ja structure + per-locale string table keyed by ja text.
 * Run: php bootstrap/road_signs_from_ja.php
 */

$ja = require dirname(__DIR__).'/lang/ja/road_signs.php';

$draftNotices = [
    'ko' => '이 번역은 원어민 검토를 기다리는 초안입니다.',
    'zh' => '本译文为草稿，尚待母语使用者审阅。',
    'vi' => 'Bản dịch này là bản nháp, đang chờ người bản ngữ rà soát.',
    'fil' => 'Ang salin na ito ay draft at naghihintay ng pagsusuri mula sa isang katutubong tagapagsalita.',
    'ne' => 'यो अनुवाद मस्यौदा हो र मातृभाषी समीक्षाको प्रतीक्षामा छ।',
    'bn' => 'এই অনুবাদটি খসড়া; মাতৃভাষী পর্যালোচকারির অপেক্ষায় রয়েছে।',
];

$localeTables = [
    'ko' => require __DIR__.'/road_signs_tables/ko.php',
    'zh' => require __DIR__.'/road_signs_tables/zh.php',
    'vi' => require __DIR__.'/road_signs_tables/vi.php',
    'fil' => require __DIR__.'/road_signs_tables/fil.php',
    'ne' => require __DIR__.'/road_signs_tables/ne.php',
    'bn' => require __DIR__.'/road_signs_tables/bn.php',
];

foreach ($localeTables as $locale => $table) {
    $data = translateTree($ja, $table);
    $data['draft_notice'] = $draftNotices[$locale];
    if ($locale === 'ko') {
        $data['title'] = '일본 도로표지 배우기';
        $data['meta_title'] = '일본 도로표지 배우기 | MenkyoNavi';
    } elseif ($locale === 'zh') {
        $data['title'] = '学习日本道路标志';
        $data['meta_title'] = '学习日本道路标志 | MenkyoNavi';
    } elseif ($locale === 'vi') {
        $data['title'] = 'Học biển báo đường bộ Nhật Bản';
        $data['meta_title'] = 'Học biển báo đường bộ Nhật Bản | MenkyoNavi';
    } elseif ($locale === 'fil') {
        $data['title'] = 'Matutong biển báo sa Japan';
        $data['meta_title'] = 'Matutong biển báo sa Japan | MenkyoNavi';
    } elseif ($locale === 'ne') {
        $data['title'] = 'जापानी सडक चिन्ह सिक्नुहोस्';
        $data['meta_title'] = 'जापानी सडक चिन्ह सिक्नुहोस् | MenkyoNavi';
    } elseif ($locale === 'bn') {
        $data['title'] = 'জাপানি সড়ক চিহ্ন শিখুন';
        $data['meta_title'] = 'জাপানি সড়ক চিহ্ন শিখুন | MenkyoNavi';
    }
    $path = dirname(__DIR__)."/lang/{$locale}/road_signs.php";
    file_put_contents($path, "<?php\n\nreturn ".exportArray($data).";\n");
    echo "Wrote {$path}\n";
}

/** @param array<string, mixed> $node @param array<string, string> $table */
function translateTree(array $node, array $table): array
{
    $out = [];
    foreach ($node as $key => $value) {
        if (is_array($value)) {
            $out[$key] = translateTree($value, $table);
        } elseif (is_string($value)) {
            $out[$key] = $table[$value] ?? $value;
        } else {
            $out[$key] = $value;
        }
    }

    return $out;
}

function exportArray(mixed $value, int $indent = 0): string
{
    $pad = str_repeat('    ', $indent);
    $padIn = str_repeat('    ', $indent + 1);

    if (! is_array($value)) {
        return var_export($value, true);
    }

    if ($value === []) {
        return '[]';
    }

    if (array_is_list($value) && ! is_array($value[0] ?? null)) {
        return '['.implode(', ', array_map(static fn ($v) => var_export($v, true), $value)).']';
    }

    $lines = ["[\n"];
    foreach ($value as $k => $v) {
        $key = is_int($k) ? (string) $k : var_export($k, true);
        $lines[] = $padIn.$key.' => '.exportArray($v, $indent + 1).",\n";
    }
    $lines[] = $pad.']';

    return implode('', $lines);
}
