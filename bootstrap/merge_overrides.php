<?php

declare(strict_types=1);

$locales = ['ko', 'zh', 'vi', 'fil', 'ne', 'bn'];

foreach ($locales as $locale) {
    $base = require __DIR__."/road_signs_maps/{$locale}.php";
    $overridePath = __DIR__."/road_signs_maps/{$locale}_overrides.php";
    if (! is_file($overridePath)) {
        echo "Skip {$locale} (no overrides)\n";

        continue;
    }
    /** @var array<string, string> $overrides */
    $overrides = require $overridePath;
    $merged = array_merge($base, $overrides);
    file_put_contents(__DIR__."/road_signs_maps/{$locale}.php", "<?php\n\nreturn ".var_export($merged, true).";\n");
    echo "Merged {$locale}\n";
}

require __DIR__.'/translate_road_signs.php';
