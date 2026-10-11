<?php

declare(strict_types=1);

/**
 * Publishes lang/{zh,vi,fil,ne,bn,ko}/road_signs.php from lang/en/road_signs.php + locale overlays.
 * Run: php bootstrap/publish_road_signs_locales.php
 */

$base = require dirname(__DIR__).'/lang/en/road_signs.php';

$draftNotices = [
    'zh' => '本译文为草稿，尚待母语使用者审阅。',
    'vi' => 'Bản dịch này là bản nháp, đang chờ người bản ngữ rà soát.',
    'fil' => 'Ang salin na ito ay draft at naghihintay ng pagsusuri mula sa isang katutubong tagapagsalita.',
    'ne' => 'यो अनुवाद मस्यौदा हो र मातृभाषी समीक्षाको प्रतीक्षामा छ।',
    'bn' => 'এই অনুবাদটি খসড়া; মাতৃভাষী পর্যালোচকারির অপেক্ষায় রয়েছে।',
    'ko' => '이 번역은 원어민 검토를 기다리는 초안입니다.',
];

$overlays = require __DIR__.'/road_signs_locale_overlays.php';

foreach ($overlays as $locale => $overlay) {
    $data = arrayReplaceRecursive($base, $overlay);
    $data['draft_notice'] = $draftNotices[$locale];
    $path = dirname(__DIR__)."/lang/{$locale}/road_signs.php";
    file_put_contents($path, "<?php\n\nreturn ".exportShort($data).";\n");
    echo "Wrote {$path}\n";
}

/** @param array<string, mixed> $base @param array<string, mixed> $overlay */
function arrayReplaceRecursive(array $base, array $overlay): array
{
    foreach ($overlay as $key => $value) {
        if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
            $base[$key] = arrayReplaceRecursive($base[$key], $value);
        } else {
            $base[$key] = $value;
        }
    }

    return $base;
}

function exportShort(mixed $value, int $indent = 0): string
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
        $items = array_map(static fn ($v) => var_export($v, true), $value);

        return '['.implode(', ', $items).']';
    }

    $lines = ["[\n"];
    foreach ($value as $k => $v) {
        $key = is_int($k) ? (string) $k : var_export($k, true);
        $lines[] = $padIn.$key.' => '.exportShort($v, $indent + 1).",\n";
    }
    $lines[] = $pad.']';

    return implode('', $lines);
}
