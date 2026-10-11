<?php

namespace Tests\Feature;

use App\Support\Content\IntersectionPriorityGuide;
use App\Support\Content\PedestrianPriorityGuide;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GuideIllustrationAccuracyTest extends TestCase
{
    /** @var list<string> */
    private const FORBIDDEN_VISIBLE_ENGLISH = [
        'Stop completely',
        'Look both ways',
        'Yield to pedestrians',
        'Intersection priority',
        'Approaching a crossing',
        'Vehicle turning right',
        'Signal control comes first',
        'Left turn (Japan)',
        'Right turn (Japan)',
        'Uncontrolled / low-visibility',
        'Scan for people and hidden cars',
        'Check both sides past parked cars',
        'About to cross',
        'No crosswalk markings',
        'Follow signal control first',
        'Do not impede / slow when required',
        'When Art.36(2) does not apply',
        'Rolling past without a full stop',
        'Full stop, then observe, then go',
        'Complete stop before the line',
        'Japanese temporary-stop: inverted',
        'Approach',
        'Observe',
        'Proceed',
        'Incorrect',
        'A Pedestrian',
        'B Cyclist',
        'C Hidden vehicle',
        'Blind spots can hide',
        'Intent to cross matters',
        'If a pedestrian is already crossing nearby',
        'Watch pedestrians and conflicting turns',
        'Keep left; watch near-side',
        'Parked cars and walls hide',
        'Reduce early',
        'When lawful',
    ];

    #[Test]
    public function all_pedestrian_guide_figure_paths_exist_and_parse(): void
    {
        foreach (config('content.pedestrian_priority.diagrams') as $key => $path) {
            $absolute = public_path((string) $path);
            $this->assertFileExists($absolute, (string) $key);
            $this->assertSvgParses($absolute, (string) $key);
        }

        foreach (PedestrianPriorityGuide::assets() as $key => $asset) {
            $absolute = public_path((string) $asset['path']);
            $this->assertFileExists($absolute, (string) $key);
            $this->assertSvgParses($absolute, (string) $key);
            $this->assertSame('project-created', $asset['origin'] ?? null);
        }

        $this->assertFileExists(public_path((string) config('content.pedestrian_priority.thumbnail')));
    }

    #[Test]
    public function all_intersection_guide_figure_paths_exist_and_parse(): void
    {
        foreach (IntersectionPriorityGuide::assets() as $key => $asset) {
            $absolute = public_path((string) $asset['path']);
            $this->assertFileExists($absolute, (string) $key);
            $this->assertSvgParses($absolute, (string) $key);
            $this->assertSame('project-created', $asset['origin'] ?? null);
        }

        $this->assertFileExists(public_path((string) config('content.intersection_priority.thumbnail')));
    }

    #[Test]
    public function guide_svgs_have_no_embedded_english_editorial_headlines(): void
    {
        $paths = collect(config('content.pedestrian_priority.diagrams', []))
            ->merge(collect(IntersectionPriorityGuide::assets())->pluck('path'))
            ->merge([
                config('content.pedestrian_priority.thumbnail'),
                config('content.intersection_priority.thumbnail'),
            ])
            ->filter()
            ->unique()
            ->values();

        foreach ($paths as $relative) {
            $svg = File::get(public_path((string) $relative));
            // Ignore accessibility <title>/<desc> metadata; check visible instructional nodes only.
            $visible = preg_replace('/<(title|desc)\b[^>]*>.*?<\/\1>/si', '', $svg) ?? $svg;

            foreach (self::FORBIDDEN_VISIBLE_ENGLISH as $phrase) {
                $this->assertStringNotContainsString(
                    $phrase,
                    $visible,
                    "{$relative} still embeds editorial English: {$phrase}",
                );
            }
        }
    }

    #[Test]
    public function temporary_stop_diagram_preserves_inverted_triangle_and_japanese_lettering(): void
    {
        $svg = File::get(public_path('images/learn/intersection-priority-stop-sign.svg'));

        $this->assertStringContainsString('止まれ', $svg);
        $this->assertStringContainsString('points="100,178 22,34 178,34"', $svg);
        $this->assertStringNotContainsString('points="180,70 260,210 100,210"', $svg);
        $this->assertStringContainsString('fill="#C62828"', $svg);
    }

    #[Test]
    public function priority_compare_reuses_priority_road_diamond_geometry(): void
    {
        $svg = File::get(public_path('images/learn/intersection-priority-compare.svg'));

        $this->assertStringContainsString('fill="#F7D117"', $svg);
        $this->assertStringContainsString('fill="#FFFFFF"', $svg);
        $this->assertDoesNotMatchRegularExpression('/transform\s*=\s*["\']rotate\(45/', $svg);
    }

    #[Test]
    public function captions_and_alts_are_localized_across_eight_languages(): void
    {
        $pedestrianKeys = ['approach', 'sightlines', 'preparing', 'intersection'];
        $intersectionKeys = [
            'hero',
            'priority_compare',
            'stop_sign',
            'stop_steps',
            'signals',
            'left_turn',
            'right_turn',
            'uncontrolled',
            'mistakes',
            'hazard',
        ];

        foreach (['ja', 'en', 'zh', 'vi', 'fil', 'ne', 'bn', 'ko'] as $locale) {
            $pedestrian = PedestrianPriorityGuide::content($locale);
            $intersection = IntersectionPriorityGuide::content($locale);

            foreach ($pedestrianKeys as $key) {
                $this->assertNotSame('', (string) data_get($pedestrian, "visuals.captions.{$key}"), "{$locale}:{$key}");
                $this->assertNotSame('', (string) data_get($pedestrian, "visuals.alts.{$key}"), "{$locale}:{$key}");
            }

            foreach ($intersectionKeys as $key) {
                $this->assertNotSame('', (string) data_get($intersection, "visuals.captions.{$key}"), "{$locale}:{$key}");
                $this->assertNotSame('', (string) data_get($intersection, "visuals.alts.{$key}"), "{$locale}:{$key}");
            }
        }
    }

    #[Test]
    public function guide_pages_render_localized_captions_without_english_svg_headlines(): void
    {
        foreach (['ja', 'en', 'bn', 'ne'] as $locale) {
            $pedestrian = PedestrianPriorityGuide::content($locale);
            $intersection = IntersectionPriorityGuide::content($locale);

            $this->withSession(['locale' => $locale])
                ->get(route('learn.traffic-rules.pedestrian-priority'))
                ->assertSuccessful()
                ->assertSee((string) data_get($pedestrian, 'visuals.captions.approach'), false)
                ->assertSee((string) data_get($pedestrian, 'visuals.captions.sightlines'), false)
                ->assertDontSee('Check both sides past parked cars', false)
                ->assertDontSee('About to cross', false);

            $this->withSession(['locale' => $locale])
                ->get(route('learn.traffic-rules.intersection-priority'))
                ->assertSuccessful()
                ->assertSee((string) data_get($intersection, 'visuals.captions.hero'), false)
                ->assertSee((string) data_get($intersection, 'visuals.captions.stop_sign'), false)
                ->assertDontSee('Signal control comes first', false)
                ->assertDontSee('Complete stop before the line', false);
        }
    }

    #[Test]
    public function related_learning_thumbnails_resolve_and_use_containment(): void
    {
        $pedestrianHtml = $this->get(route('learn.traffic-rules.pedestrian-priority'))
            ->assertSuccessful()
            ->getContent();
        $intersectionHtml = $this->get(route('learn.traffic-rules.intersection-priority'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('images/learn/intersection-priority-thumb.svg', $pedestrianHtml);
        $this->assertStringContainsString('images/learn/pedestrian-priority-thumb.svg', $intersectionHtml);
        $this->assertStringContainsString('object-contain', $pedestrianHtml);
        $this->assertStringContainsString('object-contain', $intersectionHtml);
        $this->assertStringNotContainsString('object-cover', $pedestrianHtml);
        $this->assertStringNotContainsString('object-cover', $intersectionHtml);
    }

    #[Test]
    public function interactive_scenario_ids_and_practice_answers_remain_unchanged(): void
    {
        $this->assertSame(
            IntersectionPriorityGuide::scenarioIds('ja'),
            IntersectionPriorityGuide::scenarioIds('en'),
        );
        $this->assertSame(
            PedestrianPriorityGuide::practiceAnswerMap('ja'),
            PedestrianPriorityGuide::practiceAnswerMap('en'),
        );
        $this->assertSame([1, 1, 1], PedestrianPriorityGuide::practiceAnswerMap('ja'));
        $this->assertCount(5, IntersectionPriorityGuide::scenarioIds('ja'));
    }

    #[Test]
    public function educational_sources_and_noindex_rules_remain_intact(): void
    {
        $this->assertNotEmpty(PedestrianPriorityGuide::sources());
        $this->assertNotEmpty(IntersectionPriorityGuide::sources());

        $this->assertTrue(PedestrianPriorityGuide::isIndexable('ja'));
        $this->assertNull(PedestrianPriorityGuide::robots('ja'));
        $this->assertFalse(PedestrianPriorityGuide::isIndexable('bn'));
        $this->assertSame('noindex, follow', PedestrianPriorityGuide::robots('bn'));

        $this->assertTrue(IntersectionPriorityGuide::isIndexable('ja'));
        $this->assertNull(IntersectionPriorityGuide::robots('ja'));
        $this->assertFalse(IntersectionPriorityGuide::isIndexable('ne'));
        $this->assertSame('noindex, follow', IntersectionPriorityGuide::robots('ne'));
    }

    #[Test]
    public function adsense_remains_disabled_on_guide_pages(): void
    {
        config(['services.adsense.enabled' => false]);

        $this->get(route('learn.traffic-rules.pedestrian-priority'))
            ->assertSuccessful()
            ->assertDontSee('adsbygoogle', false);

        $this->get(route('learn.traffic-rules.intersection-priority'))
            ->assertSuccessful()
            ->assertDontSee('adsbygoogle', false);
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
