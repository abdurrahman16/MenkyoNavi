<?php

namespace Tests\Feature\Translations;

use App\Enums\StagedTranslationStatus;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportEntryTranslation;
use App\Services\Translations\StagedTranslationExportService;
use App\Services\Translations\StagedTranslationImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StagedTranslationPipelineTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_export_and_import_preserve_english_source_and_support_unicode(): void
    {
        $entry = QuestionImportEntry::factory()->create([
            'english_question' => 'Overtaking is prohibited within 30 meters before a railway crossing.',
            'english_explanation' => 'This prevents accidents at railway crossings.',
            'correct_answer' => true,
            'source_group' => 'karimen_test_1',
            'original_question_number' => 20,
        ]);

        $originalQuestion = $entry->english_question;
        $originalAnswer = $entry->correct_answer;

        $export = app(StagedTranslationExportService::class);
        $payload = $export->export(locales: ['ja', 'en']);

        $this->assertSame(1, $payload['format_version']);
        $this->assertGreaterThanOrEqual(2, $payload['entry_count']);

        $jaRow = collect($payload['rows'])->firstWhere('locale', 'ja');
        $this->assertNotNull($jaRow);
        $this->assertTrue($jaRow['english_source_readonly']);
        $this->assertSame($originalQuestion, $jaRow['english_question']);

        $jaRow['question_text'] = '踏切とその手前30メートル以内では、追い越しをしてはならない。';
        $jaRow['explanation'] = '踏切での事故を防ぐために禁止されている。';
        $jaRow['status'] = StagedTranslationStatus::Draft->value;
        $jaRow['english_question'] = 'MUTATED ENGLISH SHOULD BE IGNORED';
        $jaRow['correct_answer'] = false;

        $path = storage_path('app/menkyo-exports/test-import.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode([
            'format_version' => 1,
            'rows' => [$jaRow],
        ], JSON_UNESCAPED_UNICODE));

        $summary = app(StagedTranslationImportService::class)->importFromFile($path, dryRun: false);

        $this->assertSame(1, $summary['created_count']);
        $this->assertSame(0, $summary['validation_error_count']);
        $this->assertGreaterThan(0, $summary['english_mutations_blocked']);

        $entry->refresh();
        $this->assertSame($originalQuestion, $entry->english_question);
        $this->assertSame($originalAnswer, $entry->correct_answer);

        $translation = QuestionImportEntryTranslation::query()
            ->where('question_import_entry_id', $entry->id)
            ->where('locale', 'ja')
            ->firstOrFail();

        $this->assertSame('踏切とその手前30メートル以内では、追い越しをしてはならない。', $translation->question_text);
        $this->assertSame(StagedTranslationStatus::Draft, $translation->status);
        $this->assertNotSame(StagedTranslationStatus::Reviewed, $translation->status);
    }

    #[Test]
    public function test_import_is_idempotent_and_blocks_reviewed_overwrite_without_force(): void
    {
        $entry = QuestionImportEntry::factory()->create([
            'english_question' => 'Drivers must stop at a stop sign.',
            'english_explanation' => 'A complete stop is required.',
            'correct_answer' => true,
        ]);

        QuestionImportEntryTranslation::factory()->reviewed()->create([
            'question_import_entry_id' => $entry->id,
            'locale' => 'ja',
            'question_text' => '一時停止の標識では止まらなければならない。',
            'explanation' => '完全に停止する必要がある。',
        ]);

        $row = [
            'source_entry_id' => $entry->id,
            'source_stable_id' => $entry->stableSourceId(),
            'source_entry_checksum' => $entry->source_entry_checksum,
            'locale' => 'ja',
            'question_text' => '変更された訳文',
            'explanation' => '変更された解説',
            'status' => StagedTranslationStatus::Draft->value,
        ];

        $service = app(StagedTranslationImportService::class);

        $blocked = $service->importPayload([
            'format_version' => 1,
            'rows' => [$row],
        ], dryRun: false, forceReviewedOverwrite: false);

        $this->assertSame(1, $blocked['blocked_reviewed_count']);
        $this->assertSame(
            '一時停止の標識では止まらなければならない。',
            $entry->fresh()->translationFor('ja')?->question_text
                ?? QuestionImportEntryTranslation::query()->where('question_import_entry_id', $entry->id)->where('locale', 'ja')->value('question_text'),
        );

        $forced = $service->importPayload([
            'format_version' => 1,
            'rows' => [$row],
        ], dryRun: false, forceReviewedOverwrite: true);

        $this->assertSame(1, $forced['updated_count']);

        $again = $service->importPayload([
            'format_version' => 1,
            'rows' => [$row],
        ], dryRun: false, forceReviewedOverwrite: true);

        $this->assertSame(1, $again['updated_count']);
        $this->assertSame(1, QuestionImportEntryTranslation::query()->where('locale', 'ja')->count());
    }

    #[Test]
    public function test_dry_run_import_does_not_write_and_rejects_unsupported_locale(): void
    {
        $entry = QuestionImportEntry::factory()->create();

        $summary = app(StagedTranslationImportService::class)->importPayload([
            'format_version' => 1,
            'rows' => [[
                'source_entry_id' => $entry->id,
                'source_stable_id' => $entry->stableSourceId(),
                'locale' => 'fr',
                'question_text' => 'Texte',
                'explanation' => 'Explication',
                'status' => 'draft',
            ]],
        ], dryRun: true);

        $this->assertTrue($summary['dry_run']);
        $this->assertSame(1, $summary['validation_error_count']);
        $this->assertDatabaseCount('question_import_entry_translations', 0);
    }

    #[Test]
    public function test_artisan_export_and_import_commands_round_trip_draft_status(): void
    {
        $entry = QuestionImportEntry::factory()->create([
            'english_question' => 'Seatbelts substantially reduce injury risk.',
            'english_explanation' => 'Drivers must ensure passengers wear seatbelts.',
            'correct_answer' => true,
        ]);

        $exportPath = storage_path('app/menkyo-exports/command-export.json');

        $exportExit = Artisan::call('menkyo:translations-export', [
            '--locale' => ['ja'],
            '--ensure-stubs' => true,
            '--path' => $exportPath,
        ]);

        $this->assertSame(0, $exportExit);
        $this->assertFileExists($exportPath);

        $payload = json_decode((string) File::get($exportPath), true, 512, JSON_THROW_ON_ERROR);
        $payload['rows'][0]['question_text'] = 'シートベルトは傷害のリスクを大幅に減らす。';
        $payload['rows'][0]['explanation'] = '運転者は同乗者にも着用させなければならない。';
        $payload['rows'][0]['status'] = 'draft';
        File::put($exportPath, json_encode($payload, JSON_UNESCAPED_UNICODE));

        $importExit = Artisan::call('menkyo:translations-import', [
            'file' => $exportPath,
            '--commit' => true,
        ]);

        $this->assertSame(0, $importExit);

        $translation = QuestionImportEntryTranslation::query()
            ->where('question_import_entry_id', $entry->id)
            ->where('locale', 'ja')
            ->firstOrFail();

        $this->assertSame(StagedTranslationStatus::Draft, $translation->status);
        $this->assertNull($translation->reviewed_at);
    }
}
