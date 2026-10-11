<?php

declare(strict_types=1);

require __DIR__.'/export_array.php';

$en = require dirname(__DIR__).'/lang/en/road_signs.php';
/** @var array<string, string> $map */
$map = require __DIR__.'/_ko_ui_map_core.php';
$signText = require __DIR__.'/_ko_sign_text.php';

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
$data['draft_notice'] = '이 번역은 원어민 검토를 기다리는 초안입니다.';
$data['meta_title'] = '일본 도로표지 배우기 | MenkyoNavi';
$data['title'] = '일본 도로표지 배우기';

file_put_contents(dirname(__DIR__).'/lang/ko/road_signs.php', "<?php\n\nreturn ".exportArray($data).";\n");
echo "Built ko\n";

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
