<?php

namespace Tests\Feature\Imports;

use App\Enums\QuestionImportDuplicateKind;
use App\Enums\QuestionStatus;
use App\Enums\StagedTranslationStatus;
use App\Models\Question;
use App\Models\QuestionImportBatch;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportEntryTranslation;
use App\Services\Imports\QuestionBankImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ResolvesKarimenHtmlFixture;
use Tests\Support\ResolvesKarimenObjectHtmlFixture;
use Tests\TestCase;

class KarimenObjectHtmlImportTest extends TestCase
{
    use RefreshDatabase;
    use ResolvesKarimenHtmlFixture;
    use ResolvesKarimenObjectHtmlFixture;

    private string $objectMiniPath;

    private string $tupleMiniPath;

    private string $objectFullPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->objectMiniPath = base_path('tests/Fixtures/Imports/karimen_object_mini_valid.html');
        $this->tupleMiniPath = base_path('tests/Fixtures/Imports/karimen_mini_valid.html');
        $this->objectFullPath = $this->resolveFullKarimenObjectHtmlPath();
    }

    #[Test]
    public function test_commit_imports_object_bank_without_publishing_or_touching_existing_tuple_entries(): void
    {
        $service = app(QuestionBankImportService::class);

        $tupleSummary = $service->import(
            $this->tupleMiniPath,
            dryRun: false,
            sourceKey: 'karimen_exam_trainer_en_610',
            sourceAlias: QuestionBankImportService::SOURCE_TUPLE_610,
        );

        $pilotEntry = QuestionImportEntry::query()
            ->where('source_group', 'karimen_test_1')
            ->where('original_question_number', 1)
            ->firstOrFail();

        QuestionImportEntryTranslation::factory()->create([
            'question_import_entry_id' => $pilotEntry->id,
            'locale' => 'ja',
            'question_text' => 'パイロット日本語',
            'explanation' => 'パイロット解説',
            'status' => StagedTranslationStatus::Draft,
        ]);

        $tupleCountBefore = QuestionImportEntry::query()
            ->where('question_import_batch_id', $tupleSummary['batch_id'])
            ->count();
        $questionsBefore = Question::query()->count();

        $summary = $service->import(
            $this->objectFullPath,
            dryRun: false,
            sourceAlias: QuestionBankImportService::SOURCE_OBJECT_414,
        );

        $this->assertSame(414, $summary['entry_count']);
        $this->assertSame(414, $summary['created_count']);
        $this->assertSame(0, $summary['parse_error_count']);
        $this->assertTrue($summary['questions_table_unchanged']);
        $this->assertSame($questionsBefore, Question::query()->count());

        $this->assertSame(2, QuestionImportBatch::query()->count());
        $this->assertSame($tupleCountBefore + 414, QuestionImportEntry::query()->count());
        $this->assertSame(0, QuestionImportEntry::query()->where('is_published', true)->count());

        $objectBatch = QuestionImportBatch::query()
            ->where('source_key', 'karimen_exam_trainer_en_414')
            ->firstOrFail();
        $this->assertSame(414, $objectBatch->entry_count);
        $this->assertSame(50, $objectBatch->group_counts['jaf_test_1_signals_signs_license']);

        $entry = QuestionImportEntry::query()
            ->where('question_import_batch_id', $objectBatch->id)
            ->where('source_group', 'jaf_test_1_signals_signs_license')
            ->where('original_question_number', 1)
            ->firstOrFail();

        $this->assertSame(
            'When the traffic light for perpendicular traffic turns red, you may assume your light is green and proceed.',
            $entry->english_question,
        );
        $this->assertFalse($entry->correct_answer);
        $this->assertSame(
            'Never assume — some systems turn ALL lights red simultaneously. Observe the light you are facing.',
            $entry->english_explanation,
        );
        $this->assertTrue($entry->source_metadata['trap_style']);
        $this->assertFalse($entry->is_published);

        $pilotEntry->refresh();
        $this->assertSame('パイロット日本語', $pilotEntry->translationFor('ja')?->question_text);
        $this->assertSame($tupleCountBefore, QuestionImportEntry::query()
            ->where('question_import_batch_id', $tupleSummary['batch_id'])
            ->count());
    }

    #[Test]
    public function test_dry_run_object_import_writes_nothing(): void
    {
        $service = app(QuestionBankImportService::class);

        $summary = $service->import(
            $this->objectFullPath,
            dryRun: true,
            sourceAlias: QuestionBankImportService::SOURCE_OBJECT_414,
        );

        $this->assertTrue($summary['dry_run']);
        $this->assertSame(414, $summary['entry_count']);
        $this->assertSame(0, $summary['created_count']);
        $this->assertDatabaseCount('question_import_batches', 0);
        $this->assertDatabaseCount('question_import_entries', 0);
    }

    #[Test]
    public function test_object_reimport_is_idempotent(): void
    {
        $service = app(QuestionBankImportService::class);

        $first = $service->import(
            $this->objectMiniPath,
            dryRun: false,
            sourceAlias: QuestionBankImportService::SOURCE_OBJECT_414,
        );
        $second = $service->import(
            $this->objectMiniPath,
            dryRun: false,
            sourceAlias: QuestionBankImportService::SOURCE_OBJECT_414,
        );

        $this->assertSame($first['entry_count'], $second['entry_count']);
        $this->assertSame(1, QuestionImportBatch::query()->count());
        $this->assertSame($first['entry_count'], QuestionImportEntry::query()->count());
        $this->assertSame(0, $second['created_count']);
        $this->assertSame($first['entry_count'], $second['updated_count']);
        $this->assertGreaterThan(0, $second['same_source_reimport_count']);
    }

    #[Test]
    public function test_cross_source_duplicate_and_answer_conflict_detection(): void
    {
        $service = app(QuestionBankImportService::class);

        $service->import(
            $this->tupleMiniPath,
            dryRun: false,
            sourceKey: 'karimen_exam_trainer_en_610',
            sourceAlias: QuestionBankImportService::SOURCE_TUPLE_610,
        );

        $summary = $service->import(
            $this->objectMiniPath,
            dryRun: false,
            sourceAlias: QuestionBankImportService::SOURCE_OBJECT_414,
        );

        $this->assertGreaterThan(0, $summary['exact_same_question_and_answer_count']);
        $this->assertGreaterThan(0, $summary['exact_same_question_different_answer_count']);
        $this->assertGreaterThan(0, $summary['duplicates_inside_source_count']);
        $this->assertGreaterThan(0, $summary['same_wording_different_answer_count']);

        $crossMatch = QuestionImportEntry::query()
            ->where('duplicate_kind', QuestionImportDuplicateKind::ExactCrossSourceMatch)
            ->get();

        $this->assertTrue($crossMatch->contains(
            fn (QuestionImportEntry $entry): bool => $entry->english_question === 'Mini karimen question one that is false.',
        ));

        $crossAnswerConflict = QuestionImportEntry::query()
            ->where('duplicate_kind', QuestionImportDuplicateKind::ExactQuestionDifferentAnswer)
            ->get();

        $this->assertTrue($crossAnswerConflict->contains(
            fn (QuestionImportEntry $entry): bool => $entry->english_question === 'Mini karimen question three.',
        ));

        $inside = QuestionImportEntry::query()
            ->whereIn('duplicate_kind', [
                QuestionImportDuplicateKind::IdenticalAcrossSets,
                QuestionImportDuplicateKind::DuplicateInsideSource,
            ])
            ->get();

        $this->assertTrue($inside->contains(
            fn (QuestionImportEntry $entry): bool => str_contains(
                $entry->english_question,
                'Duplicate inside object bank',
            ),
        ));

        $answerConflict = QuestionImportEntry::query()
            ->where('duplicate_kind', QuestionImportDuplicateKind::SameWordingDifferentAnswer)
            ->get();

        $this->assertTrue($answerConflict->contains(
            fn (QuestionImportEntry $entry): bool => str_contains(
                $entry->english_question,
                'Same wording different answer probe object',
            ),
        ));
    }

    #[Test]
    public function test_artisan_object_source_selection_and_missing_file(): void
    {
        $exit = Artisan::call('menkyo:import-karimen-html', [
            '--source' => 'object414',
            '--file' => $this->objectMiniPath,
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $exit);
        $output = Artisan::output();
        $this->assertStringContainsString('Dry-run preview', $output);
        $this->assertStringContainsString('object414', $output);
        $this->assertDatabaseCount('question_import_entries', 0);

        $missingExit = Artisan::call('menkyo:import-karimen-html', [
            '--source' => 'object414',
            '--file' => storage_path('app/menkyo-imports/missing-object.html'),
            '--dry-run' => true,
        ]);

        $this->assertSame(1, $missingExit);
        $this->assertStringContainsString('Import source HTML file was not found', Artisan::output());
    }

    #[Test]
    public function test_existing_exam_runner_questions_remain_unaffected(): void
    {
        $question = Question::factory()->create([
            'status' => QuestionStatus::Demo,
            'is_demo' => true,
            'japanese_text' => '既存の問題文',
        ]);

        app(QuestionBankImportService::class)->import(
            $this->objectMiniPath,
            dryRun: false,
            sourceAlias: QuestionBankImportService::SOURCE_OBJECT_414,
        );

        $question->refresh();
        $this->assertSame('既存の問題文', $question->japanese_text);
        $this->assertSame(QuestionStatus::Demo, $question->status);
        $this->assertTrue($question->is_demo);
        $this->assertSame(0, QuestionImportEntry::query()->where('is_published', true)->count());
    }

    #[Test]
    public function test_readiness_report_includes_both_sources_and_duplicate_metrics(): void
    {
        $service = app(QuestionBankImportService::class);
        $service->import(
            $this->tupleMiniPath,
            dryRun: false,
            sourceKey: 'karimen_exam_trainer_en_610',
            sourceAlias: QuestionBankImportService::SOURCE_TUPLE_610,
        );
        $service->import(
            $this->objectMiniPath,
            dryRun: false,
            sourceAlias: QuestionBankImportService::SOURCE_OBJECT_414,
        );

        $exit = Artisan::call('menkyo:question-readiness');
        $output = Artisan::output();

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('karimen_exam_trainer_en_610', $output);
        $this->assertStringContainsString('karimen_exam_trainer_en_414', $output);
        $this->assertStringContainsString('Unique content fingerprints', $output);
        $this->assertStringContainsString('Duplicate / conflict audit', $output);
    }
}
