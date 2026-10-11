<?php

namespace App\Console\Commands;

use App\Enums\StagedTranslationStatus;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportEntryTranslation;
use App\Services\Translations\TranslationQualityChecker;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('menkyo:seed-pilot-translations {--dry-run : Preview without writing} {--commit : Persist draft pilot translations}')]
#[Description('Seed unreviewed draft pilot translations for a small staged question set')]
class SeedPilotTranslationsCommand extends Command
{
    public function handle(TranslationQualityChecker $qualityChecker): int
    {
        if (app()->isProduction()) {
            $this->error('This command is disabled in production.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $commit = (bool) $this->option('commit');

        if (! $dryRun && ! $commit) {
            $this->warn('Neither --dry-run nor --commit provided. Defaulting to --dry-run.');
            $dryRun = true;
        }

        if ($dryRun && $commit) {
            $this->error('Choose either --dry-run or --commit, not both.');

            return self::FAILURE;
        }

        /** @var list<array{source_group: string, original_question_number: int, translations: array<string, array<string, mixed>>}> $pilot */
        $pilot = require database_path('data/pilot_staged_translations.php');

        $created = 0;
        $updated = 0;
        $missing = 0;
        $flagged = 0;

        $apply = function () use ($pilot, $qualityChecker, $dryRun, &$created, &$updated, &$missing, &$flagged): void {
            foreach ($pilot as $item) {
                $entry = QuestionImportEntry::query()
                    ->where('source_group', $item['source_group'])
                    ->where('original_question_number', $item['original_question_number'])
                    ->first();

                if ($entry === null) {
                    $missing++;
                    $this->warn("Missing staged entry {$item['source_group']}#{$item['original_question_number']}");

                    continue;
                }

                // Exact English retained as draft translation record.
                $englishPayload = [
                    'question_text' => $entry->english_question,
                    'explanation' => $entry->english_explanation,
                    'figure_caption' => $entry->figure_caption,
                    'nuance_note' => 'Exact original English source retained. Not independently re-verified.',
                    'status' => StagedTranslationStatus::Draft,
                    'translator_note' => 'Pilot pilot: immutable English reference copy.',
                ];

                $locales = ['en' => $englishPayload] + $item['translations'];

                foreach ($locales as $locale => $translation) {
                    if ($locale !== 'en') {
                        $translation = [
                            'question_text' => (string) ($translation['question_text'] ?? ''),
                            'explanation' => (string) ($translation['explanation'] ?? ''),
                            'figure_caption' => $entry->figure_caption === null
                                ? null
                                : (string) ($translation['figure_caption'] ?? $entry->figure_caption),
                            'nuance_note' => (string) ($translation['nuance_note'] ?? 'Draft pilot translation; not native-expert verified.'),
                            'status' => StagedTranslationStatus::Draft,
                            'translator_note' => 'Task 08B pilot draft. Requires human review before publication.',
                        ];
                    }

                    $qualityFlags = $qualityChecker->check($entry, [
                        'locale' => $locale,
                        'question_text' => $translation['question_text'],
                        'explanation' => $translation['explanation'],
                        'figure_caption' => $translation['figure_caption'] ?? null,
                    ]);

                    $status = $translation['status'];

                    if ($qualityFlags !== [] && $status === StagedTranslationStatus::Draft) {
                        $status = StagedTranslationStatus::NeedsReview;
                        $flagged++;
                    }

                    // Never auto-promote to reviewed merely because text exists.
                    if ($status->isReviewedOrHigher()) {
                        $status = StagedTranslationStatus::Draft;
                    }

                    if ($dryRun) {
                        $exists = QuestionImportEntryTranslation::query()
                            ->where('question_import_entry_id', $entry->id)
                            ->where('locale', $locale)
                            ->exists();
                        $exists ? $updated++ : $created++;

                        continue;
                    }

                    $model = QuestionImportEntryTranslation::query()->updateOrCreate(
                        [
                            'question_import_entry_id' => $entry->id,
                            'locale' => $locale,
                        ],
                        [
                            'question_text' => $translation['question_text'],
                            'explanation' => $translation['explanation'],
                            'figure_caption' => $translation['figure_caption'] ?? null,
                            'nuance_note' => $translation['nuance_note'] ?? null,
                            'status' => $status,
                            'quality_flags' => $qualityFlags,
                            'translated_at' => now(),
                            'reviewed_at' => null,
                            'translator_note' => $translation['translator_note'] ?? null,
                        ],
                    );

                    if ($model->wasRecentlyCreated) {
                        $created++;
                    } else {
                        $updated++;
                    }
                }

                if (! $dryRun) {
                    $entry->forceFill(['is_published' => false])->save();
                }
            }
        };

        if ($dryRun) {
            $apply();
        } else {
            DB::transaction($apply);
        }

        $this->info($dryRun ? 'Pilot translation dry-run complete.' : 'Pilot draft translations seeded.');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Pilot source questions', (string) count($pilot)],
                ['Created rows', (string) $created],
                ['Updated rows', (string) $updated],
                ['Missing staged entries', (string) $missing],
                ['Moved to needs_review by quality flags', (string) $flagged],
                ['All marked reviewed?', 'no'],
                ['Published?', 'no'],
            ],
        );
        $this->comment('Pilot translations are unreviewed drafts and must not be treated as native-expert verified.');

        return $missing > 0 ? self::FAILURE : self::SUCCESS;
    }
}
