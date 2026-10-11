<?php

$a = require dirname(__DIR__).'/lang/ja/road_signs.php';
$s = [];
$walk = function ($n) use (&$walk, &$s): void {
    if (is_string($n)) {
        $s[] = $n;
    } elseif (is_array($n)) {
        foreach ($n as $v) {
            $walk($v);
        }
    }
};
$walk($a);
file_put_contents(__DIR__.'/ja_strings.json', json_encode(array_values(array_unique($s)), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo count(array_unique($s));
