<?php

declare(strict_types=1);

require __DIR__.'/_patch_ko_signs.php';

// Re-load koSignText from patch file by requiring and capturing - duplicate koSignText here
$koSignText = (static function (): array {
    return require __DIR__.'/_ko_sign_text.php';
})();

$en = require dirname(__DIR__).'/lang/en/road_signs.php';

/** @var array<string, string> $map */
$map = require __DIR__.'/_ko_ui_map.php';

foreach ($en['signs'] as $id => $fields) {
    if (! isset($koSignText[$id])) {
        continue;
    }
    foreach ($fields as $key => $enVal) {
        if ($key === 'keywords' || ! is_string($enVal)) {
            continue;
        }
        if (isset($koSignText[$id][$key])) {
            $map[$enVal] = $koSignText[$id][$key];
        }
    }
    if (isset($koSignText[$id]['keywords'])) {
        // keywords stay searchable mix; map each en keyword if present
        foreach ($fields['keywords'] as $i => $enKw) {
            if (isset($koSignText[$id]['keywords'][$i])) {
                $map[$enKw] = $koSignText[$id]['keywords'][$i];
            }
        }
    }
}

$data = translateTree($en, $map);
$data['draft_notice'] = '이 번역은 원어민 검토를 기다리는 초안입니다.';
$data['meta_title'] = '일본 도로표지 배우기 | MenkyoNavi';
$data['title'] = '일본 도로표지 배우기';

require __DIR__.'/translate_road_signs.php';
file_put_contents(dirname(__DIR__).'/lang/ko/road_signs.php', "<?php\n\nreturn ".exportArray($data).";\n");
echo "Finished ko\n";

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
