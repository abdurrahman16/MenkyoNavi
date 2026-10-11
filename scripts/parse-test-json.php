<?php

$path = $argv[1] ?? 'storage/logs/last-test.json';
$raw = file_get_contents($path);
$json = json_decode($raw, true);

if (! is_array($json)) {
    echo substr($raw, 0, 2000), PHP_EOL;
    exit(1);
}

echo ($json['result'] ?? '?').' tests='.($json['tests'] ?? '?').' passed='.($json['passed'] ?? '?').PHP_EOL;

foreach ($json['failures'] ?? [] as $failure) {
    echo '- '.($failure['test'] ?? '?').PHP_EOL;
    echo '  '.str_replace("\n", ' ', substr($failure['message'] ?? '', 0, 300)).PHP_EOL;
}
