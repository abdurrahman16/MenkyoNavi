<?php

function shape($a) {
    $out = [];
    foreach ($a as $k => $v) {
        $out[$k] = is_array($v) ? shape($v) : gettype($v);
    }
    ksort($out);
    return $out;
}

function findDiff($a, $b, $path = '') {
    foreach ($a as $k => $v) {
        $p = $path === '' ? (string) $k : $path.'.'.$k;
        if (! array_key_exists($k, $b)) {
            echo "only ja: {$p}\n";
            continue;
        }
        if (is_array($v) && is_array($b[$k])) {
            findDiff($v, $b[$k], $p);
        } elseif (json_encode($v) !== json_encode($b[$k])) {
            echo "diff: {$p} ja=".json_encode($v).' en='.json_encode($b[$k])."\n";
        }
    }
    foreach ($b as $k => $v) {
        if (! array_key_exists($k, $a)) {
            echo "only en: {$path}.{$k}\n";
        }
    }
}

$ja = shape(require dirname(__DIR__).'/lang/ja/road_signs.php');
$en = shape(require dirname(__DIR__).'/lang/en/road_signs.php');
findDiff($ja, $en);
