<?php

namespace Tests\Feature;

use App\Support\Content\RoadSignsGuide;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RoadSignsAssetAccuracyTest extends TestCase
{
    /** @var list<string> */
    private const CRITICAL_SIGN_FILES = [
        'no-entry.svg',
        'railroad-crossing.svg',
        'school-zone.svg',
        'crossroads.svg',
        'pedestrian-crossing.svg',
        'priority-road.svg',
        'temporary-stop.svg',
        'slow-down.svg',
    ];

    /** @var list<string> */
    private const SCENE_FILES = [
        'road-signs-hero.svg',
        'road-signs-scene-stop.svg',
        'road-signs-scene-no-entry.svg',
        'road-signs-scene-speed.svg',
        'road-signs-scene-parking.svg',
        'road-signs-scene-crossing.svg',
        'road-signs-scene-warning.svg',
    ];

    /** @var list<string> */
    private const FORBIDDEN_SCENE_HEADLINES = [
        'Stop completely before the line',
        'Do not enter this roadway',
        'Example posted maximum: 40',
        'Parking is prohibited here',
        'Watch for people at the crossing',
        'Railroad crossing ahead — prepare early',
        'Learn Japanese road signs with verified categories',
        'Applies on the signed section',
    ];

    #[Test]
    public function every_configured_sign_illustration_exists_and_parses(): void
    {
        foreach (RoadSignsGuide::signCatalog() as $signId => $sign) {
            $path = public_path((string) $sign['illustration']);
            $this->assertFileExists($path, "Missing illustration for {$signId}");
            $this->assertSvgParses($path, $signId);
        }

        $this->assertCount(17, RoadSignsGuide::signCatalog());
    }

    #[Test]
    public function every_road_signs_asset_exists_and_parses(): void
    {
        foreach (RoadSignsGuide::assets() as $key => $asset) {
            $path = public_path((string) $asset['path']);
            $this->assertFileExists($path, (string) $key);
            $this->assertSvgParses($path, (string) $key);
            $this->assertSame('project-created', $asset['origin'] ?? null);
        }
    }

    #[Test]
    public function critical_sign_geometry_stays_inside_view_box(): void
    {
        foreach (self::CRITICAL_SIGN_FILES as $filename) {
            $path = public_path('images/learn/signs/'.$filename);
            $this->assertFileExists($path, $filename);
            $svg = File::get($path);
            $this->assertSvgParses($path, $filename);
            $this->assertDoesNotMatchRegularExpression(
                '/transform\s*=\s*["\']rotate\(45/',
                $svg,
                "{$filename} should not use mis-centered rotate(45) diamond geometry",
            );
            $this->assertMatchesRegularExpression(
                '/viewBox\s*=\s*["\']0 0 200 200["\']/',
                $svg,
                "{$filename} should use a stable 200×200 viewBox",
            );
        }
    }

    #[Test]
    public function vehicle_no_entry_uses_red_disc_with_white_bar(): void
    {
        $svg = File::get(public_path('images/learn/signs/no-entry.svg'));

        $this->assertStringContainsString('fill="#C62828"', $svg);
        $this->assertStringContainsString('<rect', $svg);
        $this->assertStringNotContainsString('fill="#1B4F9C"', $svg);
        $this->assertDoesNotMatchRegularExpression('/A78,78 0 0,1/', $svg);
        $this->assertStringNotContainsString('上半分', $svg);
    }

    #[Test]
    public function diamond_warning_and_instruction_signs_use_centered_polygons(): void
    {
        foreach (['railroad-crossing.svg', 'school-zone.svg', 'crossroads.svg', 'priority-road.svg'] as $filename) {
            $svg = File::get(public_path('images/learn/signs/'.$filename));
            $this->assertStringContainsString('points="100,40 160,100 100,160 40,100"', $svg, $filename);
            $this->assertStringContainsString('fill="#F7D117"', $svg, $filename);
            $this->assertStringContainsString('stroke-linejoin="round"', $svg, $filename);
        }

        $priority = File::get(public_path('images/learn/signs/priority-road.svg'));
        $this->assertStringContainsString('points="100,68 132,100 100,132 68,100"', $priority);

        $crossing = File::get(public_path('images/learn/signs/pedestrian-crossing.svg'));
        $this->assertStringContainsString('fill="#1B4F9C"', $crossing);
        $this->assertStringContainsString('points="100,20 170,58 170,118 100,176 30,118 30,58"', $crossing);
    }

    #[Test]
    public function temporary_stop_and_slow_down_use_inverted_triangles(): void
    {
        foreach (['temporary-stop.svg', 'slow-down.svg'] as $filename) {
            $svg = File::get(public_path('images/learn/signs/'.$filename));
            $this->assertStringContainsString('points="100,178 22,34 178,34"', $svg, $filename);
            $this->assertStringNotContainsString('points="100,22 178,168 22,168"', $svg, $filename);
        }
    }

    #[Test]
    public function scene_and_hero_svgs_have_no_embedded_english_headlines(): void
    {
        foreach (self::SCENE_FILES as $filename) {
            $svg = File::get(public_path('images/learn/'.$filename));
            $this->assertSvgParses(public_path('images/learn/'.$filename), $filename);

            foreach (self::FORBIDDEN_SCENE_HEADLINES as $headline) {
                $this->assertStringNotContainsString($headline, $svg, "{$filename} still embeds: {$headline}");
            }
        }

        $noEntryScene = File::get(public_path('images/learn/road-signs-scene-no-entry.svg'));
        $this->assertStringContainsString('fill="#C62828"', $noEntryScene);
        $this->assertStringNotContainsString('fill="#1B4F9C"', $noEntryScene);
    }

    #[Test]
    public function scene_headings_and_captions_are_localized_outside_svgs(): void
    {
        foreach (['ja', 'en', 'bn', 'ne'] as $locale) {
            $content = RoadSignsGuide::content($locale);
            $this->assertNotSame('', $content['scenes']['no_entry']['title'] ?? '');
            $this->assertNotSame('', $content['visuals']['captions']['no_entry'] ?? '');
            $this->assertNotSame('', $content['visuals']['captions']['hero'] ?? '');

            $response = $this->withSession(['locale' => $locale])
                ->get(route('learn.road-signs'));

            $response->assertSuccessful()
                ->assertSee($content['scenes']['no_entry']['title'], false)
                ->assertSee($content['visuals']['captions']['hero'], false)
                ->assertDontSee('Stop completely before the line', false)
                ->assertDontSee('Do not enter this roadway', false);
        }
    }

    #[Test]
    public function sign_cards_use_non_cropping_image_presentation(): void
    {
        $html = $this->get(route('learn.road-signs'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('object-contain', $html);
        $this->assertStringContainsString('road-sign-card__art', $html);
        $this->assertStringNotContainsString('object-cover', $html);
        $this->assertStringContainsString('images/learn/signs/no-entry.svg', $html);
        $this->assertStringContainsString('images/learn/pedestrian-priority-thumb.svg', $html);
        $this->assertStringContainsString('images/learn/intersection-priority-thumb.svg', $html);
    }

    #[Test]
    public function related_learning_thumbnails_exist(): void
    {
        $this->assertFileExists(public_path(config('content.pedestrian_priority.thumbnail')));
        $this->assertFileExists(public_path(config('content.intersection_priority.thumbnail')));
        $this->assertSvgParses(public_path(config('content.pedestrian_priority.thumbnail')), 'pedestrian-thumb');
        $this->assertSvgParses(public_path(config('content.intersection_priority.thumbnail')), 'intersection-thumb');
    }

    #[Test]
    public function recognition_practice_answers_remain_unchanged(): void
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
    }

    #[Test]
    public function no_entry_educational_copy_matches_red_disc_design(): void
    {
        foreach (['ja', 'en'] as $locale) {
            $content = RoadSignsGuide::content($locale);
            $meaning = (string) ($content['signs']['no-entry']['meaning'] ?? '');
            $this->assertStringNotContainsString('blue', mb_strtolower($meaning));
            $this->assertStringNotContainsString('青', $meaning);
            $this->assertStringNotContainsString('파란', $meaning);
        }

        $ja = RoadSignsGuide::content('ja');
        $this->assertStringContainsString('赤地', (string) $ja['signs']['no-entry']['meaning']);
        $this->assertStringContainsString('白い横棒', (string) $ja['signs']['no-entry']['meaning']);
    }

    #[Test]
    public function search_filtering_and_seo_contracts_remain_stable(): void
    {
        $filtered = RoadSignsGuide::filterSigns('warning', '踏切', 'ja');
        $this->assertNotEmpty($filtered);

        $this->get(route('learn.road-signs', ['category' => 'warning', 'q' => '踏切']))
            ->assertSuccessful()
            ->assertSee('踏切あり', false);

        $this->assertTrue(RoadSignsGuide::isIndexable('ja'));
        $this->assertNull(RoadSignsGuide::robots('ja'));
        $this->assertFalse(RoadSignsGuide::isIndexable('bn'));
        $this->assertSame('noindex, follow', RoadSignsGuide::robots('bn'));
    }

    private function assertSvgParses(string $path, string $label): void
    {
        $svg = File::get($path);
        $this->assertNotSame('', trim($svg), $label);
        $this->assertStringContainsString('<svg', $svg, $label);
        $this->assertStringNotContainsString('<script', mb_strtolower($svg), $label);

        $previous = libxml_use_internal_errors(true);
        $document = simplexml_load_string($svg);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $this->assertNotFalse($document, "SVG parse failed for {$label}");
        $this->assertSame([], $errors, "SVG XML errors for {$label}");
        $this->assertTrue(isset($document['viewBox']) || isset($document['viewbox']), "Missing viewBox on {$label}");
    }
}
