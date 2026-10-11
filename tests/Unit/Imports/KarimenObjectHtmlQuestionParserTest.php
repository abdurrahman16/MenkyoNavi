<?php

namespace Tests\Unit\Imports;

use App\Services\Imports\KarimenObjectHtmlQuestionParser;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\ResolvesKarimenObjectHtmlFixture;
use Tests\TestCase;

class KarimenObjectHtmlQuestionParserTest extends TestCase
{
    use ResolvesKarimenObjectHtmlFixture;

    private KarimenObjectHtmlQuestionParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new KarimenObjectHtmlQuestionParser;
    }

    #[Test]
    public function test_safe_parser_extracts_expected_full_object_source_entries_without_executing_javascript(): void
    {
        $path = $this->resolveFullKarimenObjectHtmlPath();

        $result = $this->parser->parseFile($path);

        $this->assertSame(414, $result->entryCount());
        $this->assertSame([], $result->errors);
        $this->assertSame([
            'jaf_test_1_signals_signs_license' => 50,
            'jaf_test_2_lanes_pedestrians_speed' => 50,
            'jaf_test_3_overtake_intersections_parking' => 50,
            'jaf_test_4_hazards_night_expressway' => 50,
            'jaf_test_5_motorcycle_accident_disaster' => 50,
            'master_test_6_stage_1_rules_signs' => 52,
            'master_test_7_stage_1_lanes_at' => 29,
            'master_test_8_stage_2_hazards_upkeep' => 47,
            'master_test_9_stage_2_parking_expressway' => 36,
        ], $result->groupCounts);
        $this->assertSame(414, array_sum($result->groupCounts));
    }

    #[Test]
    public function test_preserves_exact_english_text_answers_explanations_and_trap_metadata(): void
    {
        $path = $this->resolveFullKarimenObjectHtmlPath();
        $result = $this->parser->parseFile($path);

        $first = $result->entries[0];
        $this->assertSame('jaf_test_1_signals_signs_license', $first['source_group']);
        $this->assertSame(1, $first['original_question_number']);
        $this->assertSame(
            'When the traffic light for perpendicular traffic turns red, you may assume your light is green and proceed.',
            $first['english_question'],
        );
        $this->assertFalse($first['correct_answer']);
        $this->assertSame(
            'Never assume — some systems turn ALL lights red simultaneously. Observe the light you are facing.',
            $first['english_explanation'],
        );
        $this->assertSame('unspecified', $first['exam_family']);
        $this->assertTrue($first['source_metadata']['trap_style']);
        $this->assertSame('jaf', $first['source_metadata']['test_source']);
        $this->assertSame('Signals · Signs · License', $first['source_metadata']['test_name']);
        $this->assertContains('trap_style_metadata', $first['review_flags']);
        $this->assertContains('exam_family_unclear', $first['review_flags']);

        $withoutTrap = collect($result->entries)->first(
            fn (array $entry): bool => $entry['source_group'] === 'jaf_test_1_signals_signs_license'
                && $entry['original_question_number'] === 2,
        );

        $this->assertNotNull($withoutTrap);
        $this->assertSame(
            'Traffic lights with human pictograms apply to pedestrians and regular bicycles.',
            $withoutTrap['english_question'],
        );
        $this->assertTrue($withoutTrap['correct_answer']);
        $this->assertFalse($withoutTrap['source_metadata']['trap_style']);
    }

    #[Test]
    public function test_reports_malformed_object_entries_while_keeping_valid_ones(): void
    {
        $html = file_get_contents(base_path('tests/Fixtures/Imports/karimen_object_malformed.html'));
        $this->assertIsString($html);

        $result = $this->parser->parse($html);

        $this->assertGreaterThanOrEqual(3, count($result->errors));
        $this->assertTrue(collect($result->errors)->contains(
            fn (array $error): bool => str_contains($error['message'], 'a must be a boolean'),
        ));
        $this->assertTrue(collect($result->errors)->contains(
            fn (array $error): bool => str_contains($error['message'], 'q and e must be strings'),
        ));

        $valid = collect($result->entries)->firstWhere('english_question', 'Valid object question.');
        $this->assertNotNull($valid);
        $this->assertTrue($valid['correct_answer']);
    }

    #[Test]
    public function test_missing_source_file_throws_clear_runtime_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Import source HTML file was not found');

        $this->parser->parseFile(storage_path('app/menkyo-imports/missing-object-source.html'));
    }
}
