<?php

namespace Tests\Feature;

use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\QuestionStatement;
use App\Models\QuestionTranslation;
use App\Support\SafeRubyMarkup;
use App\Support\SupportedLocale;
use Database\Seeders\DemoQuestionSeeder;
use Database\Seeders\ExamTypeSeeder;
use Database\Seeders\QuestionCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class ExamQuestionDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_exam_question_tables_exist_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('exam_types'));
        $this->assertTrue(Schema::hasTable('question_categories'));
        $this->assertTrue(Schema::hasTable('questions'));
        $this->assertTrue(Schema::hasTable('question_translations'));
        $this->assertTrue(Schema::hasTable('question_statements'));
        $this->assertTrue(Schema::hasTable('question_statement_translations'));
        $this->assertTrue(Schema::hasTable('question_media'));
        $this->assertTrue(Schema::hasTable('source_references'));
        $this->assertTrue(Schema::hasTable('exam_type_question'));

        $this->assertTrue(Schema::hasColumns('exam_types', [
            'slug',
            'name_ja',
            'selection_rules',
            'duration_minutes',
            'max_score',
            'passing_score',
            'is_active',
        ]));

        $this->assertTrue(Schema::hasColumns('questions', [
            'uuid',
            'type',
            'status',
            'japanese_text',
            'correct_answer',
            'is_demo',
            'content_review_status',
        ]));
    }

    public function test_question_belongs_to_category_and_exam_types(): void
    {
        $category = QuestionCategory::factory()->create(['slug' => 'road-signs']);
        $gaimen = ExamType::factory()->create(['slug' => 'gaimen-kirikae']);
        $karimen = ExamType::factory()->create(['slug' => 'karimen']);

        $question = Question::factory()->create([
            'question_category_id' => $category->id,
        ]);
        $question->examTypes()->attach([$gaimen->id, $karimen->id]);

        $this->assertTrue($question->category->is($category));
        $this->assertCount(2, $question->examTypes);
        $this->assertTrue($gaimen->questions->contains($question));
    }

    public function test_translation_locale_must_be_supported_and_unique_per_question(): void
    {
        $question = Question::factory()->create();

        QuestionTranslation::factory()->create([
            'question_id' => $question->id,
            'locale' => 'en',
        ]);

        $this->expectException(InvalidArgumentException::class);
        QuestionTranslation::factory()->create([
            'question_id' => $question->id,
            'locale' => 'xx',
        ]);
    }

    public function test_legacy_tl_locale_normalizes_to_fil(): void
    {
        $this->assertSame('fil', SupportedLocale::normalize('tl'));
    }

    public function test_honmen_illustrated_group_has_three_statements(): void
    {
        $question = Question::factory()->illustrated()->demo()->create([
            'points' => 2,
        ]);

        QuestionStatement::factory()->create([
            'question_id' => $question->id,
            'sort_order' => 1,
            'correct_answer' => true,
        ]);
        QuestionStatement::factory()->create([
            'question_id' => $question->id,
            'sort_order' => 2,
            'correct_answer' => false,
        ]);
        QuestionStatement::factory()->create([
            'question_id' => $question->id,
            'sort_order' => 3,
            'correct_answer' => true,
        ]);

        $question->refresh();

        $this->assertSame(QuestionType::IllustratedGroup, $question->type);
        $this->assertNull($question->correct_answer);
        $this->assertCount(3, $question->statements);
        $this->assertSame(2, $question->points);
    }

    public function test_safe_ruby_markup_escapes_html_and_renders_readings(): void
    {
        $html = SafeRubyMarkup::fromReadingData([
            ['text' => '<script>x</script>', 'reading' => 'あ'],
            ['text' => '道路'],
        ]);

        $this->assertStringContainsString('<ruby>&lt;script&gt;x&lt;/script&gt;<rt>あ</rt></ruby>', $html);
        $this->assertStringContainsString('道路', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_seeders_are_idempotent_and_keep_demo_questions_unpublished(): void
    {
        $this->seed([
            ExamTypeSeeder::class,
            QuestionCategorySeeder::class,
            DemoQuestionSeeder::class,
        ]);

        $firstQuestionCount = Question::query()->count();
        $firstTranslationCount = QuestionTranslation::query()->count();

        $this->seed([
            ExamTypeSeeder::class,
            QuestionCategorySeeder::class,
            DemoQuestionSeeder::class,
        ]);

        $this->assertSame(3, ExamType::query()->count());
        $this->assertSame(14, QuestionCategory::query()->count());
        $this->assertSame($firstQuestionCount, Question::query()->count());
        $this->assertSame($firstTranslationCount, QuestionTranslation::query()->count());
        $this->assertGreaterThanOrEqual(15, $firstQuestionCount);
        $this->assertLessThanOrEqual(20, $firstQuestionCount);

        $this->assertSame(0, Question::query()->where('status', QuestionStatus::Published)->count());
        $this->assertTrue(Question::query()->where('is_demo', true)->exists());
        $this->assertTrue(
            Question::query()
                ->where('type', QuestionType::IllustratedGroup)
                ->where('is_demo', true)
                ->exists()
        );

        $honmen = ExamType::query()->where('slug', 'honmen')->firstOrFail();
        $this->assertSame(50, $honmen->duration_minutes);
        $this->assertSame(100, $honmen->max_score);
        $this->assertSame(90, $honmen->passing_score);
        $this->assertSame(90, $honmen->selection_rules['standard_true_false_count']);
        $this->assertSame(5, $honmen->selection_rules['illustrated_group_count']);
    }

    public function test_exam_stats_command_reports_counts_outside_production(): void
    {
        $this->seed([
            ExamTypeSeeder::class,
            QuestionCategorySeeder::class,
            DemoQuestionSeeder::class,
        ]);

        $this->artisan('menkyo:exam-stats')
            ->assertSuccessful()
            ->expectsOutputToContain('Exam types')
            ->expectsOutputToContain('Illustrated question groups');
    }

    public function test_mysql_compatible_json_and_boolean_columns_are_usable(): void
    {
        $examType = ExamType::factory()->create([
            'selection_rules' => [
                'standard_true_false_count' => 50,
                'illustrated_group_count' => 0,
            ],
            'is_active' => true,
        ]);

        $examType->refresh();

        $this->assertIsArray($examType->selection_rules);
        $this->assertTrue($examType->is_active);
        $this->assertSame(50, $examType->selection_rules['standard_true_false_count']);
    }
}
