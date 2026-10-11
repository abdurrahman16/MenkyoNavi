<?php

namespace Tests\Feature;

use App\Support\Content\RoadSignsGuide;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoadSign207BIllustrationTest extends TestCase
{
    /**
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

    public function test_railroad_crossing_svg_exists_and_parses_as_207b_train(): void
    {
        $path = public_path('images/learn/signs/railroad-crossing.svg');
        $this->assertFileExists($path);

        $svg = File::get($path);
        $this->assertStringContainsString('<svg', $svg);
        $this->assertMatchesRegularExpression('/viewBox\s*=\s*["\']0 0 200 200["\']/', $svg);
        $this->assertStringContainsString('points="100,40 160,100 100,160 40,100"', $svg);
        $this->assertStringContainsString('fill="#F7D117"', $svg);
        $this->assertStringContainsString('fill="#252A2D"', $svg);
        $this->assertStringContainsString('207-B', $svg);
        $this->assertStringContainsString('electric-train', mb_strtolower($svg));
        $this->assertStringContainsString('Pantograph', $svg);
        $this->assertStringContainsString('207-A steam locomotive', $svg);
        $this->assertStringNotContainsString('M70 70 L130 130', $svg);
        $this->assertStringNotContainsString('<script', mb_strtolower($svg));
        $this->assertStringNotContainsString('Stop completely', $svg);

        $previous = libxml_use_internal_errors(true);
        $document = simplexml_load_string($svg);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $this->assertNotFalse($document);
        $this->assertSame([], $errors);
    }

    public function test_catalog_records_207b_illustrated_variant_without_claiming_family_is_only_b(): void
    {
        $sign = RoadSignsGuide::signCatalog()['railroad-crossing'];

        $this->assertSame('railroad-crossing', $sign['id']);
        $this->assertSame('207', $sign['code']);
        $this->assertSame('warning', $sign['category']);
        $this->assertSame('踏切あり', $sign['official_name_ja']);
        $this->assertSame(['207-A', '207-B'], $sign['official_variants']);
        $this->assertSame('207-B', $sign['illustrated_variant']);
        $this->assertSame('educational_207b_source_matched', $sign['illustration_variant_status']);
        $this->assertFalse((bool) data_get($sign, 'classification_research.human_editorial_approval'));
        $this->assertFalse((bool) data_get($sign, 'classification_research.human_legal_approval'));
    }

    #[DataProvider('supportedLocales')]
    public function test_locales_describe_207b_illustration_and_remain_complete(string $locale): void
    {
        $this->assertTrue(RoadSignsGuide::hasCompleteContent($locale));
        $this->assertFalse(RoadSignsGuide::hasEnglishPlaceholderBody($locale));

        $sign = data_get(RoadSignsGuide::content($locale), 'signs.railroad-crossing');
        $this->assertIsArray($sign);
        $this->assertNotSame('', (string) ($sign['alt'] ?? ''));
        $this->assertNotSame('', (string) ($sign['meaning'] ?? ''));

        $haystack = mb_strtolower(($sign['meaning'] ?? '').' '.($sign['alt'] ?? ''));
        $this->assertTrue(
            str_contains($haystack, '207-b')
            || str_contains($haystack, '207-B')
            || str_contains((string) $sign['meaning'], '207-B')
            || str_contains((string) $sign['alt'], '207-B')
            || str_contains((string) $sign['meaning'], '২০৭-B')
            || str_contains((string) $sign['meaning'], '२०७-B'),
            "Locale {$locale} should identify the 207-B illustration",
        );
        $this->assertStringNotContainsString('simplified schematic', $haystack);
        $this->assertStringNotContainsString('簡略示意図', (string) ($sign['meaning'] ?? ''));
    }

    public function test_library_and_scene_assets_use_the_same_207b_pictogram(): void
    {
        $library = File::get(public_path('images/learn/signs/railroad-crossing.svg'));
        $scene = File::get(public_path('images/learn/road-signs-scene-warning.svg'));

        $this->assertStringContainsString('points="72,78 84,66 96,78"', $library);
        $this->assertStringContainsString('points="72,78 84,66 96,78"', $scene);
        $this->assertStringContainsString('M64 86h70a7 7 0 0 1 7 7v24a5 5 0 0 1-5 5H64a5 5 0 0 1-5-5V93a7 7 0 0 1 7-7z', $library);
        $this->assertStringContainsString('M64 86h70a7 7 0 0 1 7 7v24a5 5 0 0 1-5 5H64a5 5 0 0 1-5-5V93a7 7 0 0 1 7-7z', $scene);

        $html = $this->get(route('learn.road-signs'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('images/learn/signs/railroad-crossing.svg', $html);
        $this->assertStringContainsString('data-road-sign-card="railroad-crossing"', $html);
        $this->assertStringContainsString('images/learn/road-signs-scene-warning.svg', $html);
    }

    public function test_recognition_practice_mapping_and_other_signs_remain_unchanged(): void
    {
        $answers = collect(RoadSignsGuide::practiceItems('ja'))->mapWithKeys(
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

        $catalog = RoadSignsGuide::signCatalog();
        $this->assertCount(17, $catalog);
        foreach (self::EXPECTED_CATEGORIES as $id => $category) {
            $this->assertSame($category, $catalog[$id]['category'], $id);
        }
        $this->assertSame('326', $catalog['one-way']['code']);
        $this->assertSame('201-A', $catalog['crossroads']['code']);
        $this->assertSame('207', $catalog['railroad-crossing']['code']);
    }
}
