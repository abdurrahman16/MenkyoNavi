<?php

namespace App\Console\Commands;

use App\Models\QuestionImportEntry;
use App\Models\User;
use App\Services\Editorial\EditorialReviewService;
use App\Services\Editorial\KarimenTest1EvidenceLedger;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('menkyo:karimen-test-1-seed-evidence {--admin-email= : Administrator email recorded on audit rows} {--force : Overwrite existing non-default evidence statuses}')]
#[Description('Seed Karimen Test 1 accuracy/figure evidence ledger fields without approving answers, rights, or publication')]
class SeedKarimenTest1EvidenceCommand extends Command
{
    public function handle(EditorialReviewService $editorial, KarimenTest1EvidenceLedger $ledger): int
    {
        if (app()->isProduction()) {
            $this->error('This command is disabled in production.');

            return self::FAILURE;
        }

        $email = (string) ($this->option('admin-email') ?: '');
        $admin = $email !== ''
            ? User::query()->where('email', $email)->where('is_admin', true)->first()
            : User::query()->where('is_admin', true)->orderBy('id')->first();

        if ($admin === null) {
            $this->error('An administrator user is required to record evidence-seed audits.');

            return self::FAILURE;
        }

        $force = (bool) $this->option('force');
        $updated = 0;
        $skipped = 0;

        try {
            $entries = QuestionImportEntry::query()
                ->where('source_group', KarimenTest1EvidenceLedger::SOURCE_GROUP)
                ->orderBy('original_question_number')
                ->get();

            if ($entries->count() !== 50) {
                $this->error('Expected 50 karimen_test_1 entries, found '.$entries->count().'.');

                return self::FAILURE;
            }

            foreach ($entries as $entry) {
                $status = $entry->accuracy_evidence_status->value;
                if (! $force && $status !== 'not_checked') {
                    $skipped++;

                    continue;
                }

                $beforeAnswer = $entry->correct_answer;
                $beforeQuestion = $entry->english_question;
                $beforeRights = $entry->content_rights_status->value;
                $beforeAk = $entry->answer_key_status->value;
                $beforePublished = $entry->is_published;

                $editorial->seedAccuracyEvidenceFromLedger($entry, $admin, $ledger);
                $fresh = $entry->fresh();

                if (
                    $fresh->correct_answer !== $beforeAnswer
                    || $fresh->english_question !== $beforeQuestion
                    || $fresh->content_rights_status->value !== $beforeRights
                    || $fresh->answer_key_status->value !== $beforeAk
                    || $fresh->is_published !== $beforePublished
                ) {
                    $this->error('Abort: seed mutated immutable or approval fields for entry '.$entry->id);

                    return self::FAILURE;
                }

                $updated++;
            }
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Seeded evidence ledger for {$updated} questions (skipped {$skipped}).");
        $this->warn('Answer keys remain unverified. Content rights remain uncleared. Nothing was published.');

        return self::SUCCESS;
    }
}
