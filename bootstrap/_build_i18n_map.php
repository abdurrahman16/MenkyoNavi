<?php

declare(strict_types=1);

$locale = $argv[1] ?? null;
$txt = $argv[2] ?? null;
if ($locale === null || $txt === null) {
    fwrite(STDERR, "Usage: php bootstrap/_build_i18n_map.php <locale> <translations.txt>\n");
    exit(1);
}

$en = json_decode(file_get_contents(__DIR__.'/en_strings.json'), true, 512, JSON_THROW_ON_ERROR);
$lines = file($txt, FILE_IGNORE_NEW_LINES);
if (count($lines) !== count($en)) {
    fwrite(STDERR, 'Count mismatch: en='.count($en).' lines='.count($lines)."\n");
    exit(1);
}

$map = array_combine($en, $lines);
$dir = __DIR__.'/road_signs_i18n';
if (! is_dir($dir)) {
    mkdir($dir, 0777, true);
}
file_put_contents("{$dir}/{$locale}.php", "<?php\n\nreturn ".var_export($map, true).";\n");
echo "Built {$dir}/{$locale}.php\n";
