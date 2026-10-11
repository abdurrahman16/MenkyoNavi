<?php

namespace Tests\Unit\Imports;

use App\Services\Imports\KarimenHtmlQuestionParser;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\ResolvesKarimenHtmlFixture;
use Tests\TestCase;

class KarimenHtmlQuestionParserTest extends TestCase
{
    use ResolvesKarimenHtmlFixture;

    private KarimenHtmlQuestionParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new KarimenHtmlQuestionParser;
    }

    #[Test]
    public function test_safe_parser_extracts_expected_full_source_entries_without_executing_javascript(): void
    {
        $path = $this->resolveFullKarimenHtmlPath();

        $result = $this->parser->parseFile($path);

        $this->assertSame(610, $result->entryCount());
        $this->assertSame([], $result->errors);
        $this->assertSame([
            'karimen_test_1' => 50,
            'karimen_test_2' => 50,
            'karimen_test_3' => 50,
            'karimen_test_4' => 50,
            'karimen_test_5' => 50,
            'honmen_dl_1' => 90,
            'honmen_dl_2' => 90,
            'honmen_dl_3' => 90,
            'honmen_dl_4' => 90,
        ], $result->groupCounts);
        $this->assertNotEmpty($result->signKeys);
        $this->assertContains('pedbike', $result->signKeys);
    }

    #[Test]
    public function test_preserves_exact_english_text_answers_explanations_and_illustration_references(): void
    {
        $path = $this->resolveFullKarimenHtmlPath();
        $result = $this->parser->parseFile($path);

        $first = $result->entries[0];
        $this->assertSame('karimen_test_1', $first['source_group']);
        $this->assertSame(1, $first['original_question_number']);
        $this->assertSame(
            'Even when proceeding on a one-way street, vehicles may not drive on the right-hand side of the road.',
            $first['english_question'],
        );
        $this->assertFalse($first['correct_answer']);
        $this->assertSame(
            'Vehicles MAY proceed on the right-hand side if the road is one-way.',
            $first['english_explanation'],
        );

        $illustrated = collect($result->entries)->first(
            fn (array $entry): bool => $entry['source_group'] === 'karimen_test_1'
                && $entry['original_question_number'] === 2,
        );

        $this->assertNotNull($illustrated);
        $this->assertSame('pedbike', $illustrated['sign_key']);
        $this->assertSame('Pedestrian + bicycle crossing sign', $illustrated['figure_caption']);
        $this->assertSame('available', $illustrated['media_availability']);
    }

    #[Test]
    public function test_reports_malformed_entries_while_keeping_valid_ones(): void
    {
        $html = file_get_contents(base_path('tests/Fixtures/Imports/karimen_malformed.html'));
        $this->assertIsString($html);

        $result = $this->parser->parse($html);

        $this->assertGreaterThanOrEqual(2, count($result->errors));
        $this->assertTrue(collect($result->errors)->contains(
            fn (array $error): bool => str_contains($error['message'], 'answer must be 0 or 1'),
        ));
        $this->assertTrue(collect($result->errors)->contains(
            fn (array $error): bool => str_contains($error['message'], 'expected 3-5 fields'),
        ));

        $valid = collect($result->entries)->firstWhere('english_question', 'Valid question one.');
        $this->assertNotNull($valid);
        $this->assertFalse($valid['correct_answer']);
    }

    #[Test]
    public function test_missing_source_file_throws_clear_runtime_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Import source HTML file was not found');

        $this->parser->parseFile(base_path('tests/Fixtures/Imports/does-not-exist.html'));
    }
}
