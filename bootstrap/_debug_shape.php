<?php

function shape($a) {
    $out = [];
    foreach ($a as $k => $v) {
        $out[$k] = is_array($v) ? shape($v) : gettype($v);
    }
    ksort($out);
    return $out;
}

$ja = shape(require dirname(__DIR__).'/lang/ja/road_signs.php');
$en = shape(require dirname(__DIR__).'/lang/en/road_signs.php');
echo json_encode($ja) === json_encode($en) ? 'equal' : 'different';
echo "\n";
if (json_encode($ja) !== json_encode($en)) {
    echo substr(json_encode($ja), 0, 500);
    echo "\n---\n";
    echo substr(json_encode($en), 0, 500);
}
