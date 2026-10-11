<?php

declare(strict_types=1);

$locales = ['ko', 'zh', 'vi', 'fil', 'ne', 'bn'];

$draftNotices = [
    'ko' => '이 번역은 원어민 검토를 기다리는 초안입니다.',
    'zh' => '本译文为草稿，尚待母语使用者审阅。',
    'vi' => 'Bản dịch này là bản nháp, đang chờ người bản ngữ rà soát.',
    'fil' => 'Ang salin na ito ay draft at naghihintay ng pagsusuri mula sa isang katutubong tagapagsalita.',
    'ne' => 'यो अनुवाद मस्यौदा हो र मातृभाषी समीक्षाको प्रतीक्षामा छ।',
    'bn' => 'এই অনুবাদটি খসড়া; মাতৃভাষী পর্যালোচকারির অপেক্ষায় রয়েছে।',
];

$en = require dirname(__DIR__).'/lang/en/road_signs.php';

foreach ($locales as $locale) {
    $map = require __DIR__."/road_signs_maps/{$locale}.php";
    $data = translateTree($en, $map);
    $data['draft_notice'] = $draftNotices[$locale];
    $path = dirname(__DIR__)."/lang/{$locale}/road_signs.php";
    file_put_contents($path, "<?php\n\nreturn ".exportArray($data).";\n");
    echo "Wrote {$path}\n";
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
