<?php

require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = app(App\Services\Editorial\OriginalKarimenExpansionStagingService::class);
$manifest = $service->manifest();
$candidates = $service->candidates();

$buckets = [];
$assess = [];
$byBatch = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0];

foreach ($candidates as $row) {
    $buckets[$row['topic_bucket']] = ($buckets[$row['topic_bucket']] ?? 0) + 1;
    $assess[$row['research_assessment']] = ($assess[$row['research_assessment']] ?? 0) + 1;
    $byBatch[$row['batch']] = ($byBatch[$row['batch']] ?? 0) + 1;
}

$lines = [];
$lines[] = '# Karimen Original Expansion 001 — Editorial Packet (Task 48)';
$lines[] = '';
$lines[] = '**Collection:** `menkyo_original_karimen_expansion_001`  ';
$lines[] = '**Prepared:** '.$manifest['prepared_at'].'  ';
$lines[] = '**Sources accessed:** '.$manifest['sources_accessed_at'].'  ';
$lines[] = '**Staging:** `php artisan menkyo:stage-original-karimen-expansion {--dry-run|--commit} {--batch=A|B|C|D}`  ';
$lines[] = '**Service:** `App\\Services\\Editorial\\OriginalKarimenExpansionStagingService`';
$lines[] = '';
$lines[] = 'Research-supported drafts only. **Human-approved count: 0.** **Published count: 0.**';
$lines[] = '';
$lines[] = 'Pilot candidates `:1`–`:10` and Task 47 revision proposals are **unchanged**. Task 46 translation drafts (**56**) are **unchanged**. No seven-locale translations were generated for these 40 candidates.';
$lines[] = '';
$lines[] = '## Summary';
$lines[] = '';
$lines[] = '| Metric | Count |';
$lines[] = '| --- | ---: |';
$lines[] = '| Target new candidates | 40 |';
$lines[] = '| Actual drafted | '.count($candidates).' |';
$lines[] = '| Batch A (11–20) | '.$byBatch['A'].' |';
$lines[] = '| Batch B (21–30) | '.$byBatch['B'].' |';
$lines[] = '| Batch C (31–40) | '.$byBatch['C'].' |';
$lines[] = '| Batch D (41–50) | '.$byBatch['D'].' |';
$lines[] = '| `supported_as_written` | '.($assess['supported_as_written'] ?? 0).' |';
$lines[] = '| `supported_with_clarification` | '.($assess['supported_with_clarification'] ?? 0).' |';
$lines[] = '| Evidence-unavailable / withheld drafts | 0 |';
$lines[] = '| Human-approved | 0 |';
$lines[] = '| Published | 0 |';
$lines[] = '';
$lines[] = '### Topic distribution (drafting targets were approximate)';
$lines[] = '';
$lines[] = '| Topic bucket | Count |';
$lines[] = '| --- | ---: |';
foreach ($buckets as $bucket => $count) {
    $lines[] = '| '.$bucket.' | '.$count.' |';
}
$lines[] = '';
$lines[] = '### Official sources examined';
$lines[] = '';
foreach ($manifest['sources_inspected'] as $source) {
    $lines[] = '- '.$source['label'].': '.$source['url'].' (accessed '.$source['accessed_at'].')';
}
$lines[] = '';
$lines[] = $manifest['law_version_note'];
$lines[] = '';
$lines[] = '---';
$lines[] = '';

foreach (['A', 'B', 'C', 'D'] as $batch) {
    $lines[] = '## Batch '.$batch;
    $lines[] = '';
    foreach ($candidates as $row) {
        if ($row['batch'] !== $batch) {
            continue;
        }
        $answer = $row['correct_answer'] ? '正しい（True）' : '誤り（False）';
        $lines[] = '### '.$row['candidate_id'];
        $lines[] = '';
        $lines[] = '1. **ID:** `'.$row['candidate_id'].'`  ';
        $lines[] = '2. **Topic:** '.$row['subject_ja'].' (`'.$row['subject'].'` / '.$row['topic_bucket'].')  ';
        $lines[] = '3. **Japanese question:** '.$row['question_ja'].'  ';
        $lines[] = '4. **Proposed answer:** '.$answer.'  ';
        $lines[] = '5. **Explanation:** '.$row['explanation_ja'].'  ';
        $lines[] = '6. **Official source:** '.$row['source_url'].'  ';
        $lines[] = '7. **Exact provision:** '.$row['exact_provision'].'  ';
        $lines[] = '8. **Conditions/exceptions:** '.implode(' / ', $row['exceptions']).'  ';
        $lines[] = '9. **Research assessment:** `'.$row['research_assessment'].'`  ';
        $lines[] = '10. **Human-review questions:** '.implode(' ', $row['human_review_questions']).'  ';
        $lines[] = '11. **Publication status:** unpublished  ';
        $lines[] = '';
    }
}

$lines[] = '## Staging and gates';
$lines[] = '';
$lines[] = '- Dry-run does not mutate records.';
$lines[] = '- `--commit` is required to write; idempotent by stable `source_group` + `original_question_number`.';
$lines[] = '- Human-protected rows are skipped.';
$lines[] = '- Staging never sets human accuracy / editorial / rights / answer-key / publication approvals.';
$lines[] = '- Full Karimen pool size requirements remain unchanged; demo exams remain available.';
$lines[] = '';
$lines[] = 'Do not treat this Markdown file as a reviewer signature.';
$lines[] = '';

$path = __DIR__.'/../../../docs/KARIMEN_ORIGINAL_EXPANSION_001.md';
file_put_contents($path, implode("\n", $lines));
echo "Wrote {$path}\n";
