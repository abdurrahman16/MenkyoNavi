<?php

require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = app(App\Services\Editorial\OriginalKarimenExpansionStagingService::class);
$candidates = $service->candidates();
$buckets = [];
$assess = [];

foreach ($candidates as $row) {
    $buckets[$row['topic_bucket']] = ($buckets[$row['topic_bucket']] ?? 0) + 1;
    $assess[$row['research_assessment']] = ($assess[$row['research_assessment']] ?? 0) + 1;
}

echo 'total='.count($candidates).PHP_EOL;
print_r($buckets);
print_r($assess);
echo 'first='.$candidates[0]['candidate_id'].' last='.$candidates[array_key_last($candidates)]['candidate_id'].PHP_EOL;
