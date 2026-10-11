<?php

$a = require dirname(__DIR__).'/lang/en/road_signs.php';
$s = [];
$walk = function ($n) use (&$walk, &$s): void {
    if (is_string($n)) {
        $s[$n] = $n;
    } elseif (is_array($n)) {
        foreach ($n as $v) {
            $walk($v);
        }
    }
};
$walk($a);
file_put_contents(__DIR__.'/en_strings.json', json_encode(array_keys($s), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
