<?php

$a = require dirname(__DIR__).'/lang/en/road_signs.php';
$s = [];
$walk = function ($n) use (&$walk, &$s): void {
    if (is_string($n)) {
        $s[$n] = true;
    } elseif (is_array($n)) {
        foreach ($n as $v) {
            $walk($v);
        }
    }
};
$walk($a);
echo count($s);
