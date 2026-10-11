<?php

function shape($a) {
    $out = [];
    foreach ($a as $k => $v) {
        $out[$k] = is_array($v) ? shape($v) : gettype($v);
    }
    ksort($out);
    return $out;
}

$ja = require dirname(__DIR__).'/lang/ja/road_signs.php';
$en = require dirname(__DIR__).'/lang/en/road_signs.php';

function diff($a, $b, $path = '') {
    foreach ($a as $k => $v) {
        $p = $path.'.'.$k;
        if (! array_key_exists($k, $b)) {
            echo "missing in b: {$p}\n";
        } elseif (is_array($v) && is_array($b[$k])) {
            diff($v, $b[$k], $p);
        } elseif (is_array($v) !== is_array($b[$k])) {
            echo "type mismatch {$p}\n";
        }
    }
    foreach ($b as $k => $v) {
        if (! array_key_exists($k, $a)) {
            echo "extra in b: {$path}.{$k}\n";
        }
    }
}

diff(shape($ja), shape($en));
