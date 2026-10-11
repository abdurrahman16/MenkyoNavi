<?php

require __DIR__.'/../vendor/autoload.php';

use Illuminate\Support\Arr;

$en = Arr::dot(include __DIR__.'/../lang/en/home.php');

foreach (['ja', 'zh', 'vi', 'fil', 'ne', 'bn', 'ko'] as $locale) {
    $path = __DIR__."/../lang/{$locale}/home.php";
    $cur = file_exists($path) ? Arr::dot(include $path) : [];
    $missing = array_diff_key($en, $cur);
    $extra = array_diff_key($cur, $en);
    echo "{$locale} missing=".count($missing).' extra='.count($extra).PHP_EOL;

    if ($missing !== []) {
        echo '  missing: '.implode(', ', array_keys($missing)).PHP_EOL;
    }

    if ($extra !== []) {
        echo '  extra: '.implode(', ', array_keys($extra)).PHP_EOL;
    }
}
