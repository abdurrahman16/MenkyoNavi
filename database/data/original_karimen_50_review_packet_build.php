<?php

declare(strict_types=1);
use App\Models\QuestionImportEntry;
use App\Services\Editorial\KarimenReleaseReadinessService;
use Illuminate\Contracts\Console\Kernel;

/**
 * Builds docs/KARIMEN_ORIGINAL_50_REVIEW_PACKET.md from staged catalogs + revision proposals.
 * Run: php database/data/original_karimen_50_review_packet_build.php
 */
$root = dirname(__DIR__, 2);

require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$entryIdsByCandidate = QuestionImportEntry::query()
    ->whereIn('source_group', [
        'menkyo_original_karimen_pilot_001',
        'menkyo_original_karimen_expansion_001',
    ])
    ->get(['id', 'source_group', 'original_question_number', 'source_metadata'])
    ->mapWithKeys(function (QuestionImportEntry $entry): array {
        $candidateId = (string) data_get($entry->source_metadata, 'candidate_id', $entry->stableSourceId());

        return [$candidateId => (int) $entry->id];
    })
    ->all();

$pilotRevisions = require $root.'/database/data/original_karimen_pilot_001_source_revisions.php';
$expansionRevisions = require $root.'/database/data/original_karimen_expansion_001_source_revisions.php';

$pilotCatalog = require $root.'/database/data/original_karimen_pilot_001.php';
$pilotCandidates = is_array($pilotCatalog['candidates'] ?? null) ? $pilotCatalog['candidates'] : [];
$pilotAudit = require $root.'/database/data/original_karimen_pilot_001_accuracy_audit.php';
$pilotAuditByNumber = [];
foreach (($pilotAudit['candidates'] ?? []) as $number => $auditRow) {
    if (is_array($auditRow)) {
        $pilotAuditByNumber[(int) $number] = $auditRow;
    }
}

$expansionCandidates = [];
foreach (['batch_a.php', 'batch_b.php', 'batch_c.php', 'batch_d.php'] as $batchFile) {
    $path = $root.'/database/data/original_karimen_expansion_001/'.$batchFile;
    $rows = require $path;
    foreach ($rows as $row) {
        $expansionCandidates[] = $row;
    }
}

if ($pilotCandidates === []) {
    fwrite(STDERR, "Unable to load pilot candidates.\n");
    exit(1);
}

$rows = [];

foreach ($pilotCandidates as $candidate) {
    $number = (int) ($candidate['number'] ?? $candidate['original_question_number'] ?? 0);
    $id = (string) ($candidate['candidate_id'] ?? 'menkyo_original_karimen_pilot_001:'.$number);
    $assessment = (string) ($pilotAuditByNumber[$number]['research_assessment']
        ?? $candidate['research_assessment']
        ?? '—');
    $rows[] = [
        'id' => $id,
        'collection' => 'menkyo_original_karimen_pilot_001',
        'topic' => (string) ($candidate['subject_ja'] ?? $candidate['subject'] ?? $candidate['legal_topic'] ?? '—'),
        'answer' => ! empty($candidate['correct_answer']) ? 'True' : 'False',
        'provision' => (string) ($candidate['exact_provision'] ?? $candidate['source_section'] ?? '—'),
        'research' => $assessment,
        'human_accuracy' => 'pending',
        'editorial' => 'pending',
        'answer_key' => 'pending',
        'rights' => 'pending',
        'publication' => 'unpublished',
        'needs_human_decision' => in_array($number, [1, 8], true) || $assessment === 'supported_with_clarification',
    ];
}

foreach ($expansionCandidates as $candidate) {
    $number = (int) $candidate['number'];
    $assessment = (string) $candidate['research_assessment'];
    $rows[] = [
        'id' => (string) $candidate['candidate_id'],
        'collection' => 'menkyo_original_karimen_expansion_001',
        'topic' => (string) ($candidate['subject_ja'] ?? $candidate['subject']),
        'answer' => ! empty($candidate['correct_answer']) ? 'True' : 'False',
        'provision' => (string) $candidate['exact_provision'],
        'research' => $assessment,
        'human_accuracy' => 'pending',
        'editorial' => 'pending',
        'answer_key' => 'pending',
        'rights' => 'pending',
        'publication' => 'unpublished',
        'needs_human_decision' => in_array($number, [26, 34, 49], true) || $assessment === 'supported_with_clarification',
    ];
}

usort($rows, function (array $a, array $b): int {
    preg_match('/:(\d+)$/', $a['id'], $am);
    preg_match('/:(\d+)$/', $b['id'], $bm);

    return ((int) ($am[1] ?? 0)) <=> ((int) ($bm[1] ?? 0));
});

$supported = count(array_filter($rows, fn ($r) => $r['research'] === 'supported_as_written'));
$clarification = count(array_filter($rows, fn ($r) => $r['research'] === 'supported_with_clarification'));

$release = app(KarimenReleaseReadinessService::class)->report();

$lines = [];
$lines[] = '# Karimen Original 50 — Human Review Packet';
$lines[] = '';
$lines[] = '**Prepared:** '.date('Y-m-d').' (Task 51)';
$lines[] = '**Collections:** `menkyo_original_karimen_pilot_001` (1–10) + `menkyo_original_karimen_expansion_001` (11–50)';
$lines[] = '**Review index:** `/admin/questions/original-karimen-pilot`';
$lines[] = '**Private exam preview:** `/admin/questions/original-karimen-exam-preview`';
$lines[] = '**Release check:** `php artisan menkyo:karimen-release-check`';
$lines[] = '**Activation guide:** `docs/KARIMEN_JAPANESE_PUBLIC_ACTIVATION.md`';
$lines[] = '';
$lines[] = 'Legal research and research assessments are **not** human approvals.';
$lines[] = '**Human accuracy accepted: '.$release['human_accuracy_accepted_count'].'.** **Editorial approved: '.$release['editorial_approved_count'].'.** **Answer-key verified: '.$release['answer_key_verified_count'].'.** **Rights cleared: '.$release['rights_cleared_count'].'.** **Published: '.$release['published_count'].'.** **Public Karimen eligible: '.$release['japanese_live_pool_eligible_count'].'/50.**';
$lines[] = '';
$lines[] = '## Summary';
$lines[] = '';
$lines[] = '| Metric | Count |';
$lines[] = '| --- | ---: |';
$lines[] = '| Total original candidates | '.count($rows).' |';
$lines[] = '| Research `supported_as_written` | '.$supported.' |';
$lines[] = '| Research `supported_with_clarification` | '.$clarification.' |';
$lines[] = '| Pending source-revision proposals | 5 (`:1`, `:8`, `:26`, `:34`, `:49`) |';
$lines[] = '| Human accuracy approved | '.$release['human_accuracy_accepted_count'].' |';
$lines[] = '| Editorial approved | '.$release['editorial_approved_count'].' |';
$lines[] = '| Answer-key verified | '.$release['answer_key_verified_count'].' |';
$lines[] = '| Rights cleared | '.$release['rights_cleared_count'].' |';
$lines[] = '| Published | '.$release['published_count'].' |';
$lines[] = '| Japanese public Karimen available | '.(($release['japanese_public_karimen_can_start'] ?? false) ? 'yes' : 'no').' |';
$lines[] = '';
$lines[] = '## Reviewer actions required';
$lines[] = '';
$lines[] = '1. Open `/admin/questions/original-karimen-pilot` and process candidates in order (Previous/Next).';
$lines[] = '2. Priority first: `:1`, `:8`, `:26`, `:34`, `:49` — decide source revision (apply / reject / request changes / defer).';
$lines[] = '3. Record genuine human accuracy decision with notes for the current fingerprint.';
$lines[] = '4. Separately record editorial approval, answer-key verification, rights clearance, and Japanese ready-for-publication.';
$lines[] = '5. Publish only when all gates pass for that candidate. No approve-all shortcut.';
$lines[] = '6. Re-run `php artisan menkyo:karimen-release-check` until Japanese public Karimen reports available.';
$lines[] = '';
$lines[] = '## Priority clarification candidates';
$lines[] = '';
foreach (['menkyo_original_karimen_pilot_001:1', 'menkyo_original_karimen_pilot_001:8', 'menkyo_original_karimen_expansion_001:26', 'menkyo_original_karimen_expansion_001:34', 'menkyo_original_karimen_expansion_001:49'] as $priorityId) {
    $entryId = $entryIdsByCandidate[$priorityId] ?? null;
    $adminPath = $entryId ? '/admin/questions/original-karimen-pilot/'.$entryId : '/admin/questions/original-karimen-pilot';
    $lines[] = '- `'.$priorityId.'` → `'.$adminPath.'`';
}
$lines[] = '';
$lines[] = '## Compact table (all 50)';
$lines[] = '';
$lines[] = '| ID | Admin review | Topic | Answer | Legal reference | Research | Human accuracy | Editorial | Answer-key | Rights | Publication | Needs human decision |';
$lines[] = '| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |';

foreach ($rows as $row) {
    $entryId = $entryIdsByCandidate[$row['id']] ?? null;
    $adminPath = $entryId ? '/admin/questions/original-karimen-pilot/'.$entryId : '/admin/questions/original-karimen-pilot';
    $lines[] = '| `'.$row['id'].'` | `'.$adminPath.'` | '.$row['topic'].' | '.$row['answer'].' | '.$row['provision'].' | `'.$row['research'].'` | '.$row['human_accuracy'].' | '.$row['editorial'].' | '.$row['answer_key'].' | '.$row['rights'].' | '.$row['publication'].' | '.($row['needs_human_decision'] ? 'YES' : 'review queue').' |';
}

$lines[] = '';
$lines[] = '---';
$lines[] = '';
$lines[] = '## Five clarification candidates — full proposed revisions';
$lines[] = '';
$lines[] = 'These proposals are pending authenticated reviewer action. Current staged Japanese wording is preserved until a human applies a revision.';
$lines[] = '';

foreach ([
    ['pilot', 1, $pilotRevisions],
    ['pilot', 8, $pilotRevisions],
    ['expansion', 26, $expansionRevisions],
    ['expansion', 34, $expansionRevisions],
    ['expansion', 49, $expansionRevisions],
] as [$label, $number, $catalog]) {
    $proposal = $catalog['proposals'][$number];
    $lines[] = '### '.$proposal['candidate_id'].' ('.$label.')';
    $lines[] = '';
    $lines[] = '1. **Issue:** '.$proposal['issue_summary'];
    $lines[] = '2. **Exact provision:** '.$proposal['exact_provision'];
    $lines[] = '3. **Source URL:** '.$proposal['source_url'];
    $lines[] = '4. **Statute excerpt:** '.$proposal['statute_excerpt'];
    $lines[] = '5. **Current question (JA):** '.$proposal['current_question_ja'];
    $lines[] = '6. **Current explanation (JA):** '.$proposal['current_explanation_ja'];
    $lines[] = '7. **Current proposed answer:** '.(! empty($proposal['current_correct_answer']) ? 'True' : 'False');
    $lines[] = '8. **Proposed question (JA):** '.$proposal['proposed_question_ja'];
    $lines[] = '9. **Proposed explanation (JA):** '.$proposal['proposed_explanation_ja'];
    $lines[] = '10. **Proposed answer:** '.(! empty($proposal['proposed_correct_answer']) ? 'True' : 'False');
    $lines[] = '11. **Why revision helps:** '.$proposal['why_revision_improves_accuracy'];
    $lines[] = '12. **Remaining ambiguity / exceptions:** '.$proposal['remaining_ambiguity'];
    $lines[] = '13. **Human revision decision status:** `'.$proposal['human_revision_decision_status'].'`';
    $lines[] = '14. **Research supported (not human approval):** '.(! empty($proposal['research_supported']) ? 'true' : 'false');
    $lines[] = '';
}

$lines[] = '---';
$lines[] = '';
$lines[] = '## Safety boundaries for this sprint';
$lines[] = '';
$lines[] = '- No fabricated human approvals or reviewer identities.';
$lines[] = '- No automatic publication or exam-pool promotion.';
$lines[] = '- Pilot `:1` / `:8` Task 47 proposals retained pending human action.';
$lines[] = '- Expansion `:26` / `:34` / `:49` Task 49 proposals pending human action.';
$lines[] = '- Existing 56 translation drafts and 1,024 imported English records unchanged.';
$lines[] = '- `ADSENSE_ENABLED` remains false.';
$lines[] = '';

$out = $root.'/docs/KARIMEN_ORIGINAL_50_REVIEW_PACKET.md';
file_put_contents($out, implode("\n", $lines)."\n");
echo "Wrote {$out} rows=".count($rows)." supported={$supported} clarification={$clarification}\n";
