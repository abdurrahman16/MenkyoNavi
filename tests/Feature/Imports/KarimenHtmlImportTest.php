<?php

namespace Tests\Feature\Imports;

use App\Enums\QuestionImportDuplicateKind;
use App\Enums\QuestionStatus;
use App\Models\Question;
use App\Models\QuestionImportBatch;
use App\Models\QuestionImportEntry;
use App\Services\Imports\KarimenHtmlQuestionParser;
use App\Services\Imports\KarimenQuestionImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ResolvesKarimenHtmlFixture;
use Tests\TestCase;

class KarimenHtmlImportTest extends TestCase
{
    use RefreshDatabase;
    use ResolvesKarimenHtmlFixture;

    private string $miniPath;

    private string $fullPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->miniPath = base_path('tests/Fixtures/Imports/karimen_mini_valid.html');
        $this->fullPath = $this->resolveFullKarimenHtmlPath();
    }

    #[Test]
    public function test_commit_imports_staging_entries_and_leaves_questions_unpublished(): void
    {
        $service = app(KarimenQuestionImportService::class);
        $questionsBefore = Question::query()->count();

        $summary = $service->import($this->fullPath, dryRun: false, sourceKey: 'fixture_full_610');

        $this->assertSame(610, $summary['entry_count']);
        $this->assertSame(610, $summary['created_count']);
        $this->assertSame(0, $summary['parse_error_count']);
        $this->assertTrue($summary['questions_table_unchanged']);
        $this->assertSame($questionsBefore, Question::query()->count());

        $this->assertDatabaseCount('question_import_batches', 1);
        $this->assertDatabaseCount('question_import_entries', 610);

        $this->assertSame(0, QuestionImportEntry::query()->where('is_published', true)->count());
        $this->assertSame(0, Question::query()->where('status', QuestionStatus::Published)->count());

        $batch = QuestionImportBatch::query()->firstOrFail();
        $this->assertSame('en', $batch->source_locale);
        $this->assertSame(50, $batch->group_counts['karimen_test_1']);
        $this->assertSame(90, $batch->group_counts['honmen_dl_1']);

        $entry = QuestionImportEntry::query()
            ->where('source_group', 'karimen_test_1')
            ->where('original_question_number', 1)
            ->firstOrFail();

        $this->assertSame(
            'Even when proceeding on a one-way street, vehicles may not drive on the right-hand side of the road.',
            $entry->english_question,
        );
        $this->assertFalse($entry->correct_answer);
        $this->assertSame(
            'Vehicles MAY proceed on the right-hand side if the road is one-way.',
            $entry->english_explanation,
        );
        $this->assertArrayNotHasKey('japanese_text', $entry->getAttributes());
        $this->assertArrayNotHasKey('explanation_ja', $entry->getAttributes());
    }

    #[Test]
    public function test_dry_run_does_not_write_staging_or_question_records(): void
    {
        $service = app(KarimenQuestionImportService::class);

        $summary = $service->import($this->fullPath, dryRun: true, sourceKey: 'fixture_dry_run');

        $this->assertTrue($summary['dry_run']);
        $this->assertSame(610, $summary['entry_count']);
        $this->assertSame(0, $summary['created_count']);
        $this->assertDatabaseCount('question_import_batches', 0);
        $this->assertDatabaseCount('question_import_entries', 0);
        $this->assertDatabaseCount('questions', 0);
    }

    #[Test]
    public function test_reimport_is_idempotent_and_marks_same_source_reimport(): void
    {
        $service = app(KarimenQuestionImportService::class);

        $first = $service->import($this->miniPath, dryRun: false, sourceKey: 'fixture_mini');
        $second = $service->import($this->miniPath, dryRun: false, sourceKey: 'fixture_mini');

        $this->assertSame($first['entry_count'], $second['entry_count']);
        $this->assertSame(1, QuestionImportBatch::query()->count());
        $this->assertSame($first['entry_count'], QuestionImportEntry::query()->count());
        $this->assertSame($first['entry_count'], $second['updated_count']);
        $this->assertSame(0, $second['created_count']);
        $this->assertGreaterThan(0, $second['same_source_reimport_count']);
    }

    #[Test]
    public function test_duplicate_detection_distinguishes_identical_across_sets_and_answer_conflicts(): void
    {
        $service = app(KarimenQuestionImportService::class);
        $service->import($this->miniPath, dryRun: false, sourceKey: 'fixture_dupes');

        $identical = QuestionImportEntry::query()
            ->where('duplicate_kind', QuestionImportDuplicateKind::IdenticalAcrossSets)
            ->get();

        $this->assertNotEmpty($identical);
        $this->assertTrue($identical->contains(
            fn (QuestionImportEntry $entry): bool => $entry->source_group === 'honmen_dl_1'
                && str_contains($entry->english_question, 'Duplicate content across sets'),
        ));

        $answerConflict = QuestionImportEntry::query()
            ->where('duplicate_kind', QuestionImportDuplicateKind::SameWordingDifferentAnswer)
            ->get();

        $this->assertNotEmpty($answerConflict);
        $this->assertTrue($answerConflict->contains(
            fn (QuestionImportEntry $entry): bool => str_contains($entry->english_question, 'Same wording different answer'),
        ));
    }

    #[Test]
    public function test_artisan_command_dry_run_and_missing_file_reporting(): void
    {
        $exit = Artisan::call('menkyo:import-karimen-html', [
            '--file' => $this->fullPath,
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Dry-run preview', Artisan::output());
        $this->assertDatabaseCount('question_import_entries', 0);

        $missingExit = Artisan::call('menkyo:import-karimen-html', [
            '--file' => storage_path('app/menkyo-imports/missing-source.html'),
            '--dry-run' => true,
        ]);

        $this->assertSame(1, $missingExit);
        $this->assertStringContainsString('Import source HTML file was not found', Artisan::output());
    }

    #[Test]
    public function test_artisan_commit_stages_records_without_publishing_questions(): void
    {
        $exit = Artisan::call('menkyo:import-karimen-html', [
            '--file' => $this->miniPath,
            '--commit' => true,
            '--source-key' => 'command_mini',
        ]);

        $this->assertSame(0, $exit);
        $this->assertGreaterThan(0, QuestionImportEntry::query()->count());
        $this->assertSame(0, QuestionImportEntry::query()->where('is_published', true)->count());
        $this->assertDatabaseCount('questions', 0);
    }

    #[Test]
    public function test_existing_exam_question_functionality_remains_unaffected_by_import_tables(): void
    {
        $question = Question::factory()->create([
            'status' => QuestionStatus::Demo,
            'is_demo' => true,
            'japanese_text' => '既存の問題文',
        ]);

        app(KarimenQuestionImportService::class)
            ->import($this->miniPath, dryRun: false, sourceKey: 'fixture_existing');

        $question->refresh();
        $this->assertSame('既存の問題文', $question->japanese_text);
        $this->assertSame(QuestionStatus::Demo, $question->status);
        $this->assertTrue($question->is_demo);
        $this->assertDatabaseHas('question_import_entries', [
            'is_published' => false,
        ]);
    }

    #[Test]
    public function test_parser_and_import_preserve_illustration_media_status(): void
    {
        $parser = app(KarimenHtmlQuestionParser::class);
        $parsed = $parser->parseFile($this->fullPath);
        $illustrated = collect($parsed->entries)->first(
            fn (array $entry): bool => $entry['sign_key'] === 'pedbike',
        );

        $this->assertNotNull($illustrated);
        $this->assertSame('available', $illustrated['media_availability']);

        app(KarimenQuestionImportService::class)
            ->import($this->fullPath, dryRun: false, sourceKey: 'fixture_media');

        $stored = QuestionImportEntry::query()
            ->where('sign_key', 'pedbike')
            ->where('source_group', 'karimen_test_1')
            ->where('original_question_number', 2)
            ->firstOrFail();

        $this->assertSame('Pedestrian + bicycle crossing sign', $stored->figure_caption);
        $this->assertSame('available', $stored->media_availability->value);
    }

    #[Test]
    public function test_default_storage_path_is_used_when_file_option_omitted_and_present(): void
    {
        $target = storage_path('app/menkyo-imports/karimen_exam_trainer (1).html');
        File::ensureDirectoryExists(dirname($target));
        $backup = is_file($target) ? file_get_contents($target) : null;
        File::copy($this->miniPath, $target);

        try {
            $exit = Artisan::call('menkyo:import-karimen-html', [
                '--commit' => true,
                '--source-key' => 'default_path_mini',
            ]);

            $this->assertSame(0, $exit);
            $this->assertGreaterThan(0, QuestionImportEntry::query()->count());
        } finally {
            if ($backup === null) {
                File::delete($target);
            } else {
                File::put($target, $backup);
            }
        }
    }
}
