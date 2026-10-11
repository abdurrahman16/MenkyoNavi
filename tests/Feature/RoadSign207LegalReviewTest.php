<?php

namespace Tests\Feature;

use App\Enums\QuestionStatus;
use App\Models\Question;
use App\Models\QuestionImportEntry;
use App\Support\Content\RoadSignsGuide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoadSign207LegalReviewTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Frozen category map — Task 41 must not silently rewrite unrelated signs.
     *
     * @var array<string, string>
     */
    private const EXPECTED_CATEGORIES = [
        'temporary-stop' => 'regulatory',
        'no-entry' => 'regulatory',
        'max-speed' => 'regulatory',
        'no-parking' => 'regulatory',
        'no-stopping' => 'regulatory',
        'slow-down' => 'regulatory',
        'one-way' => 'regulatory',
        'no-vehicles' => 'regulatory',
        'railroad-crossing' => 'warning',
        'school-zone' => 'warning',
        'crossroads' => 'warning',
        'pedestrian-crossing' => 'instruction',
        'priority-road' => 'instruction',
        'destination-guide' => 'guide',
        'distance-plate' => 'supplementary',
        'time-plate' => 'supplementary',
        'vehicle-type-plate' => 'supplementary',
    ];

    /**
     * @return array<string, array{0: string}>
     */
    public static function supportedLocales(): array
    {
        return [
            'ja' => ['ja'],
            'en' => ['en'],
            'zh' => ['zh'],
            'vi' => ['vi'],
            'fil' => ['fil'],
            'ne' => ['ne'],
            'bn' => ['bn'],
            'ko' => ['ko'],
        ];
    }

    public function test_railroad_crossing_uses_official_207_series_code(): void
    {
        $catalog = RoadSignsGuide::signCatalog();
        $sign = $catalog['railroad-crossing'];

        $this->assertSame('railroad-crossing', $sign['id']);
        $this->assertSame('踏切あり', $sign['official_name_ja']);
        $this->assertSame('207', $sign['code']);
        $this->assertNotSame('201', $sign['code']);
        $this->assertSame(['207-A', '207-B'], $sign['official_variants']);
        $this->assertSame('warning', $sign['category']);
        $this->assertSame('警戒標識', data_get(RoadSignsGuide::manifest(), 'categories.warning.official_ja'));
        $this->assertSame('207-B', $sign['illustrated_variant'] ?? '');
        $this->assertSame(
            'educational_207b_source_matched',
            $sign['illustration_variant_status'],
        );
        $this->assertSame('source_supported', data_get($sign, 'classification_research.status'));
        $this->assertSame('201', data_get($sign, 'classification_research.previous_catalog_code'));
        $this->assertSame('207', data_get($sign, 'classification_research.corrected_catalog_code'));
        $this->assertFalse((bool) data_get($sign, 'classification_research.human_editorial_approval'));
        $this->assertFalse((bool) data_get($sign, 'classification_research.human_legal_approval'));
        $this->assertSame('2026-10-11', data_get($sign, 'classification_research.checked_at'));
        $this->assertFileExists(base_path((string) data_get($sign, 'classification_research.evidence_doc')));
        $this->assertContains('egov-sign-order', $sign['source_ids']);
        $this->assertContains('mlit-sign-list', $sign['source_ids']);
    }

    public function test_crossroads_retains_201_a_identification(): void
    {
        $crossroads = RoadSignsGuide::signCatalog()['crossroads'];

        $this->assertSame('crossroads', $crossroads['id']);
        $this->assertSame('201-A', $crossroads['code']);
        $this->assertSame(['201-A'], $crossroads['official_variants']);
        $this->assertSame('201-A', $crossroads['illustrated_variant']);
        $this->assertSame('┼形道路交差点あり', $crossroads['official_name_ja']);
        $this->assertSame('warning', $crossroads['category']);
        $this->assertNotSame(
            RoadSignsGuide::signCatalog()['railroad-crossing']['code'],
            $crossroads['code'],
        );
    }

    public function test_railroad_crossing_internal_id_and_category_remain_stable(): void
    {
        $this->assertArrayHasKey('railroad-crossing', RoadSignsGuide::signCatalog());
        $this->assertSame('warning', RoadSignsGuide::signCatalog()['railroad-crossing']['category']);

        $html = $this->get(route('learn.road-signs'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('data-road-sign-card="railroad-crossing"', $html);
        $this->assertStringContainsString('id="sign-railroad-crossing"', $html);
        $this->assertStringContainsString('images/learn/signs/railroad-crossing.svg', $html);
        $this->assertStringContainsString('踏切あり', $html);
        $this->assertStringContainsString('┼形道路交差点あり', $html);
        $this->assertStringContainsString('（207）', $html);
        $this->assertStringContainsString('（201-A）', $html);
    }

    #[DataProvider('supportedLocales')]
    public function test_eight_locales_remain_complete_for_railroad_crossing(string $locale): void
    {
        $this->assertTrue(RoadSignsGuide::hasCompleteContent($locale));
        $this->assertFalse(RoadSignsGuide::hasEnglishPlaceholderBody($locale));

        $content = RoadSignsGuide::content($locale);
        $sign = data_get($content, 'signs.railroad-crossing');
        $this->assertIsArray($sign);

        foreach (['title', 'meaning_short', 'meaning', 'must_do', 'example', 'misconception', 'alt'] as $field) {
            $this->assertNotSame('', (string) ($sign[$field] ?? ''), "{$locale}.{$field}");
        }
        $this->assertNotEmpty($sign['keywords'] ?? []);

        $meaning = (string) $sign['meaning'];
        $this->assertTrue(
            str_contains($meaning, '207')
            || str_contains($meaning, '২০৭')
            || str_contains($meaning, '२०७'),
            "Locale {$locale} railroad-crossing meaning should reference 207",
        );

        if ($locale !== 'ja') {
            $this->assertSame('draft_translation', data_get(RoadSignsGuide::manifest(), "locales.{$locale}.status"));
            $this->assertFalse(RoadSignsGuide::isIndexable($locale));
        }
    }

    public function test_railroad_crossing_and_crossroads_svg_paths_resolve(): void
    {
        foreach (['railroad-crossing', 'crossroads'] as $id) {
            $path = public_path((string) RoadSignsGuide::signCatalog()[$id]['illustration']);
            $this->assertFileExists($path, $id);

            $svg = File::get($path);
            $this->assertStringContainsString('<svg', $svg);
            $this->assertStringContainsString('fill="#F7D117"', $svg);
            $this->assertMatchesRegularExpression('/viewBox\s*=\s*["\']0 0 200 200["\']/', $svg);
            $this->assertStringContainsString('points="100,40 160,100 100,160 40,100"', $svg);

            $previous = libxml_use_internal_errors(true);
            $document = simplexml_load_string($svg);
            $errors = libxml_get_errors();
            libxml_clear_errors();
            libxml_use_internal_errors($previous);

            $this->assertNotFalse($document, $id);
            $this->assertSame([], $errors, $id);
        }

        $railroad = File::get(public_path('images/learn/signs/railroad-crossing.svg'));
        $this->assertStringContainsString('207-B', $railroad);
        $this->assertStringContainsString('electric-train', mb_strtolower($railroad));
        $this->assertStringContainsString('fill="#F7D117"', $railroad);
        $this->assertStringContainsString('fill="#252A2D"', $railroad);
        $this->assertStringNotContainsString('simplified black crossing mark', mb_strtolower($railroad));
        $this->assertStringNotContainsString('<script', mb_strtolower($railroad));
    }

    public function test_recognition_practice_answer_mappings_remain_unchanged(): void
    {
        $practice = RoadSignsGuide::practiceItems('ja');
        $this->assertCount(7, $practice);

        $answers = collect($practice)->mapWithKeys(
            fn (array $item): array => [(string) $item['sign_id'] => (int) $item['answer']]
        )->all();

        $this->assertSame([
            'temporary-stop' => 1,
            'no-entry' => 1,
            'max-speed' => 1,
            'time-plate' => 1,
            'slow-down' => 1,
            'one-way' => 1,
            'railroad-crossing' => 1,
        ], $answers);

        foreach (array_keys(self::supportedLocales()) as $locale) {
            $localized = RoadSignsGuide::practiceItems($locale);
            $this->assertCount(7, $localized);
            foreach ($localized as $index => $item) {
                $this->assertSame($practice[$index]['sign_id'], $item['sign_id']);
                $this->assertSame($practice[$index]['answer'], $item['answer']);
            }
        }
    }

    public function test_search_and_category_filter_still_include_railroad_crossing(): void
    {
        foreach (['踏切', '207', '207-a', '207-b', 'railroad'] as $term) {
            $matches = RoadSignsGuide::filterSigns('warning', $term, 'ja');
            $this->assertTrue(
                collect($matches)->contains(fn (array $sign): bool => $sign['id'] === 'railroad-crossing'),
                "Expected railroad-crossing for warning filter term [{$term}]",
            );
        }

        $crossroadsMatches = RoadSignsGuide::filterSigns('warning', '201-a', 'ja');
        $this->assertTrue(
            collect($crossroadsMatches)->contains(fn (array $sign): bool => $sign['id'] === 'crossroads'),
        );

        $this->get(route('learn.road-signs', ['category' => 'warning', 'q' => '踏切']))
            ->assertSuccessful()
            ->assertSee('data-road-sign-card="railroad-crossing"', false)
            ->assertSee('踏切あり', false);
    }

    public function test_source_metadata_for_207_correction_is_retained(): void
    {
        $sources = collect(RoadSignsGuide::sources())->keyBy('id');

        $this->assertTrue($sources->has('egov-sign-order'));
        $this->assertTrue($sources->has('mlit-sign-list'));
        $this->assertSame('2026-10-11', $sources['egov-sign-order']['checked_at']);
        $this->assertSame('2026-10-11', $sources['mlit-sign-list']['checked_at']);

        $supports = implode(' ', $sources['mlit-sign-list']['supports'] ?? []);
        $this->assertStringContainsString('207-A/B', $supports);
        $this->assertStringContainsString('201-A', $supports);
        $this->assertStringNotContainsString('201 踏切あり', $supports);
    }

    public function test_unrelated_sign_records_remain_frozen_except_crossroads_official_name(): void
    {
        $catalog = RoadSignsGuide::signCatalog();
        $this->assertCount(17, $catalog);
        $this->assertSame(array_keys(self::EXPECTED_CATEGORIES), array_keys($catalog));

        $codes = [];
        foreach ($catalog as $id => $sign) {
            $this->assertSame(self::EXPECTED_CATEGORIES[$id], $sign['category'], $id);
            $this->assertSame('verified', $sign['review_state']);
            $this->assertNotSame('', (string) ($sign['code'] ?? ''));
            $code = (string) $sign['code'];
            $this->assertArrayNotHasKey($code, $codes, "Duplicate official code {$code}");
            $codes[$code] = $id;
        }

        $this->assertSame('207', $catalog['railroad-crossing']['code']);
        $this->assertSame('201-A', $catalog['crossroads']['code']);
        $this->assertSame('326', $catalog['one-way']['code']);
        $this->assertSame('regulatory', $catalog['one-way']['category']);
        $this->assertSame('instruction', $catalog['pedestrian-crossing']['category']);
    }

    public function test_imported_questions_do_not_become_public_via_road_signs_page(): void
    {
        $beforeQuestions = Schema::hasTable('questions') ? Question::query()->count() : 0;
        $beforeEntries = Schema::hasTable('question_import_entries')
            ? QuestionImportEntry::query()->count()
            : 0;

        $this->get(route('learn.road-signs'))->assertSuccessful();
        RoadSignsGuide::filterSigns('warning', '踏切', 'ja');

        if (Schema::hasTable('questions')) {
            $this->assertSame($beforeQuestions, Question::query()->count());
            $this->assertSame(0, Question::query()->where('status', QuestionStatus::Published)->count());
        }
        if (Schema::hasTable('question_import_entries')) {
            $this->assertSame($beforeEntries, QuestionImportEntry::query()->count());
            $this->assertSame(0, QuestionImportEntry::query()->where('is_published', true)->count());
        }
    }
}
