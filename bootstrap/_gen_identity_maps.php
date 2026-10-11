<?php

$strings = json_decode(file_get_contents(__DIR__.'/en_strings.json'), true, 512, JSON_THROW_ON_ERROR);
$map = array_combine($strings, $strings);

foreach (['ko', 'zh', 'vi', 'fil', 'ne', 'bn'] as $locale) {
    $dir = __DIR__.'/road_signs_maps';
    if (! is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents("{$dir}/{$locale}.php", "<?php\n\nreturn ".var_export($map, true).";\n");
    echo "Identity map {$locale}\n";
}
