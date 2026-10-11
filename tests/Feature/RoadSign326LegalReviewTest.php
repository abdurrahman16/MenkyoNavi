<?php

namespace Tests\Feature;

use App\Support\Content\RoadSignsGuide;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoadSign326LegalReviewTest extends TestCase
{
    /**
     * Frozen category map for the 17-sign library — Task 40 must not silently rewrite other signs.
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

    public function test_sign_326_keeps_authoritative_regulatory_classification(): void
    {
        $catalog = RoadSignsGuide::signCatalog();
        $oneWay = $catalog['one-way'];

        $this->assertSame('一方通行', $oneWay['official_name_ja']);
        $this->assertSame('326', $oneWay['code']);
        $this->assertSame('regulatory', $oneWay['category']);
        $this->assertSame('規制標識', data_get(RoadSignsGuide::manifest(), 'categories.regulatory.official_ja'));
        $this->assertSame(['326-A', '326-B'], $oneWay['official_variants']);
        $this->assertSame('326-B', $oneWay['illustrated_variant']);
        $this->assertSame('source_supported', data_get($oneWay, 'classification_research.status'));
        $this->assertSame('regulatory', data_get($oneWay, 'classification_research.conclusion'));
        $this->assertSame('規制標識', data_get($oneWay, 'classification_research.official_category_ja'));
        $this->assertFalse((bool) data_get($oneWay, 'classification_research.human_editorial_approval'));
        $this->assertFalse((bool) data_get($oneWay, 'classification_research.human_legal_approval'));
        $this->assertSame('2026-10-11', data_get($oneWay, 'classification_research.checked_at'));
        $this->assertFileExists(base_path((string) data_get($oneWay, 'classification_research.evidence_doc')));
        $this->assertContains('egov-sign-order', $oneWay['source_ids']);
        $this->assertContains('mlit-sign-list', $oneWay['source_ids']);
        $this->assertContains('mlit-sign-categories', $oneWay['source_ids']);
        $this->assertContains('326の2-A', $oneWay['related_official_codes']);
        $this->assertContains('特定小型原動機付自転車・自転車一方通行', $oneWay['related_official_names_ja']);
    }

    public function test_official_variants_and_related_codes_remain_distinct(): void
    {
        $oneWay = RoadSignsGuide::signCatalog()['one-way'];

        $this->assertNotContains('326の2-A', $oneWay['official_variants']);
        $this->assertNotContains('326の2-B', $oneWay['official_variants']);
        $this->assertNotSame('指示標識', data_get($oneWay, 'classification_research.official_category_ja'));
        $this->assertNotSame('instruction', $oneWay['category']);
    }

    #[DataProvider('supportedLocales')]
    public function test_localized_category_labels_and_one_way_meaning_stay_regulatory(string $locale): void
    {
        $enriched = collect(RoadSignsGuide::enrichedSigns($locale))
            ->firstWhere('id', 'one-way');

        $this->assertIsArray($enriched);
        $this->assertSame('regulatory', $enriched['category']);
        $this->assertSame('規制標識', $enriched['category_official_ja']);
        $this->assertNotSame('', $enriched['category_label']);
        $this->assertSame(
            (string) data_get(RoadSignsGuide::content($locale), 'categories.regulatory.title'),
            $enriched['category_label'],
        );

        $meaning = mb_strtolower((string) $enriched['meaning']);
        $this->assertTrue(
            str_contains($meaning, '326') || str_contains($meaning, '৩২৬') || str_contains($meaning, '३२६'),
            "Locale {$locale} meaning should reference official code 326",
        );
        $this->assertStringNotContainsString('指示標識', (string) $enriched['meaning']);
        $this->assertStringNotContainsString('instruction sign', $meaning);

        $response = $this->withSession(['locale' => $locale])
            ->get(route('learn.road-signs'));

        $response->assertSuccessful()
            ->assertSee('data-road-sign-card="one-way"', false)
            ->assertSee($enriched['category_label'], false)
            ->assertSee('一方通行', false);
    }

    public function test_search_and_filter_find_one_way_by_name_code_and_variants(): void
    {
        foreach (['一方通行', '326', '326-a', '326-b', 'one way'] as $term) {
            $matches = RoadSignsGuide::filterSigns('regulatory', $term, 'ja');
            $this->assertTrue(
                collect($matches)->contains(fn (array $sign): bool => $sign['id'] === 'one-way'),
                "Expected one-way for regulatory filter term [{$term}]",
            );
        }

        $instructionOnly = RoadSignsGuide::filterSigns('instruction', '一方通行', 'ja');
        $this->assertFalse(
            collect($instructionOnly)->contains(fn (array $sign): bool => $sign['id'] === 'one-way'),
        );

        $this->get(route('learn.road-signs', ['category' => 'regulatory', 'q' => '一方通行']))
            ->assertSuccessful()
            ->assertSee('data-road-sign-card="one-way"', false)
            ->assertSee('規制標識', false);
    }

    public function test_one_way_svg_path_remains_valid_vertical_blue_arrow_plate(): void
    {
        $path = public_path('images/learn/signs/one-way.svg');
        $this->assertFileExists($path);

        $svg = File::get($path);
        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('fill="#1B4F9C"', $svg);
        $this->assertStringContainsString('fill="#ffffff"', $svg);
        $this->assertStringContainsString('<polygon', $svg);
        $this->assertMatchesRegularExpression('/viewBox\s*=\s*["\']0 0 200 200["\']/', $svg);
        $this->assertStringContainsString('326-B', $svg);
        $this->assertStringNotContainsString('<script', mb_strtolower($svg));

        $previous = libxml_use_internal_errors(true);
        $document = simplexml_load_string($svg);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $this->assertNotFalse($document);
        $this->assertSame([], $errors);

        // Tall plate geometry (vertical / 326-B educational schematic).
        $this->assertMatchesRegularExpression(
            '/<rect[^>]*width="104"[^>]*height="144"/',
            $svg,
        );
    }

    public function test_recognition_practice_mappings_for_seven_items_remain_unchanged(): void
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

    public function test_unrelated_sign_classifications_remain_frozen(): void
    {
        $catalog = RoadSignsGuide::signCatalog();
        $this->assertCount(17, $catalog);
        $this->assertSame(array_keys(self::EXPECTED_CATEGORIES), array_keys($catalog));

        $codes = [];
        foreach ($catalog as $id => $sign) {
            $this->assertSame(self::EXPECTED_CATEGORIES[$id], $sign['category'], $id);
            $this->assertSame('verified', $sign['review_state']);
            $code = (string) $sign['code'];
            $this->assertArrayNotHasKey($code, $codes, "Duplicate code {$code}");
            $codes[$code] = $id;
        }

        $this->assertSame('instruction', $catalog['pedestrian-crossing']['category']);
        $this->assertSame('instruction', $catalog['priority-road']['category']);
        $this->assertSame('supplementary', $catalog['distance-plate']['category']);
    }

    #[DataProvider('supportedLocales')]
    public function test_draft_translation_statuses_remain_intact(string $locale): void
    {
        $localeMeta = data_get(RoadSignsGuide::manifest(), "locales.{$locale}");
        $this->assertIsArray($localeMeta);

        if ($locale === 'ja') {
            $this->assertSame('authored', $localeMeta['status']);
            $this->assertTrue((bool) $localeMeta['indexable']);
            $this->assertTrue(RoadSignsGuide::isIndexable('ja'));
            $this->assertNull(RoadSignsGuide::robots('ja'));
        } else {
            $this->assertSame('draft_translation', $localeMeta['status']);
            $this->assertSame('pending_native_review', $localeMeta['review']);
            $this->assertFalse((bool) $localeMeta['indexable']);
            $this->assertFalse((bool) $localeMeta['native_review']);
            $this->assertFalse(RoadSignsGuide::isIndexable($locale));
            $this->assertSame('noindex, follow', RoadSignsGuide::robots($locale));
        }
    }

    public function test_source_manifest_includes_rechecked_326_authorities(): void
    {
        $sources = collect(RoadSignsGuide::sources())->keyBy('id');

        $this->assertTrue($sources->has('egov-sign-order'));
        $this->assertTrue($sources->has('mlit-sign-list'));
        $this->assertTrue($sources->has('mlit-sign-categories'));
        $this->assertSame('2026-10-11', $sources['egov-sign-order']['checked_at']);
        $this->assertSame('2026-10-11', $sources['mlit-sign-list']['checked_at']);
        $this->assertSame('2026-10-11', $sources['mlit-sign-categories']['checked_at']);
        $this->assertSame(
            'https://laws.e-gov.go.jp/law/335M50004002003/',
            $sources['egov-sign-order']['url'],
        );
        $this->assertSame(
            'https://www.mlit.go.jp/road/sign/sign/douro/ichiran.pdf',
            $sources['mlit-sign-list']['url'],
        );
        $this->assertSame(
            'https://www.mlit.go.jp/road/soudan/soudan_04a_01.html',
            $sources['mlit-sign-categories']['url'],
        );
    }
}
