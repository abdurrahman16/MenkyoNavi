<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\QuestionImportEntry;
use App\Support\Content\IntersectionPriorityGuide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IntersectionPriorityGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_guide_route_renders_japanese_default_content_and_sections(): void
    {
        $this->get(route('learn.traffic-rules.intersection-priority'))
            ->assertSuccessful()
            ->assertSee(__('intersection_priority.title', [], 'ja'), false)
            ->assertSee(__('intersection_priority.intro', [], 'ja'), false)
            ->assertSee(__('intersection_priority.fundamentals.title', [], 'ja'), false)
            ->assertSee(__('intersection_priority.stop_signs.title', [], 'ja'), false)
            ->assertSee(__('intersection_priority.signals.title', [], 'ja'), false)
            ->assertSee(__('intersection_priority.turning.title', [], 'ja'), false)
            ->assertSee(__('intersection_priority.uncontrolled.title', [], 'ja'), false)
            ->assertSee(__('intersection_priority.mistakes.title', [], 'ja'), false)
            ->assertSee(__('intersection_priority.scenarios.title', [], 'ja'), false)
            ->assertSee(__('intersection_priority.keypoints.title', [], 'ja'), false)
            ->assertSee(__('intersection_priority.faq_heading', [], 'ja'), false)
            ->assertSee(__('intersection_priority.sources_heading', [], 'ja'), false)
            ->assertSee('data-educational-guide', false)
            ->assertSee('data-intersection-guide', false)
            ->assertSee('data-guide-toc', false)
            ->assertSee('data-guide-sources', false)
            ->assertSee('data-path-reveal', false)
            ->assertSee('data-stop-steps', false)
            ->assertSee('data-hazard-reveal', false)
            ->assertSee('<noscript>', false)
            ->assertSee('https://laws.e-gov.go.jp/law/335AC0000000105', false)
            ->assertSee('https://www.npa.go.jp/bureau/traffic/anzen/poster/r8_leaflet_japan_anzenunten_jp.pdf', false)
            ->assertSee(route('learn.traffic-rules.pedestrian-priority', absolute: false), false)
            ->assertSee(route('exams.karimen', absolute: false), false)
            ->assertSee('viewport', false);
    }

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

    #[DataProvider('supportedLocales')]
    public function test_eight_locales_serve_complete_guide_content(string $locale): void
    {
        $this->assertTrue(IntersectionPriorityGuide::hasCompleteContent($locale));
        $this->assertFalse(IntersectionPriorityGuide::hasEnglishPlaceholderBody($locale));
        $this->assertFileExists(lang_path($locale.'/intersection_priority.php'));

        $content = IntersectionPriorityGuide::content($locale);
        $this->assertNotSame('', (string) data_get($content, 'visuals.alts.hero'));
        $this->assertNotSame('', (string) data_get($content, 'visuals.captions.hero'));
        $this->assertNotSame('', (string) data_get($content, 'meta_title'));
        $this->assertNotSame('', (string) data_get($content, 'meta_description'));
        $this->assertCount(5, data_get($content, 'scenarios.items', []));
        $this->assertSame(
            IntersectionPriorityGuide::scenarioIds('ja'),
            IntersectionPriorityGuide::scenarioIds($locale)
        );

        $response = $this->withSession(['locale' => $locale])
            ->get(route('learn.traffic-rules.intersection-priority'));

        $response->assertSuccessful()
            ->assertSee(__('intersection_priority.title', [], $locale), false)
            ->assertSee(__('intersection_priority.toc_label', [], $locale), false)
            ->assertSee(__('intersection_priority.visuals.alts.hero', [], $locale), false)
            ->assertSee(__('intersection_priority.interaction_hazard_show', [], $locale), false)
            ->assertSee('lang="'.$locale.'"', false)
            ->assertSee('<noscript>', false);

        if ($locale !== 'ja') {
            $response->assertSee(__('intersection_priority.draft_notice', [], $locale), false);
            $this->assertSame('draft_translation', IntersectionPriorityGuide::localeStatus($locale));
            $this->assertFalse(IntersectionPriorityGuide::isIndexable($locale));
            $this->assertSame('noindex, follow', IntersectionPriorityGuide::robots($locale));
            $response->assertSee('noindex, follow', false);
            $this->assertNull(IntersectionPriorityGuide::jsonLd($locale));
        }

        if ($locale === 'ja') {
            $this->assertSame('authored', IntersectionPriorityGuide::localeStatus($locale));
            $this->assertTrue(IntersectionPriorityGuide::isIndexable($locale));
            $this->assertNull(IntersectionPriorityGuide::robots($locale));
            $this->assertNotNull(IntersectionPriorityGuide::jsonLd($locale));
        }
    }

    public function test_missing_translation_falls_back_to_japanese(): void
    {
        $this->assertFalse(IntersectionPriorityGuide::hasCompleteContent('xx'));
        $content = IntersectionPriorityGuide::content('xx');
        $this->assertSame(__('intersection_priority.title', [], 'ja'), $content['title']);
        $this->assertNull(IntersectionPriorityGuide::jsonLd('xx'));
        $this->assertSame('noindex, follow', IntersectionPriorityGuide::robots('xx'));
    }

    public function test_all_configured_assets_exist_locally_with_metadata(): void
    {
        $assets = IntersectionPriorityGuide::assets();
        $this->assertNotEmpty($assets);

        foreach ($assets as $key => $asset) {
            $this->assertIsArray($asset, $key);
            $this->assertNotSame('', $asset['path'] ?? '', $key);
            $this->assertFileExists(public_path($asset['path']));
            $this->assertSame('project-created', $asset['origin'] ?? null, $key);
            $this->assertSame('original-menkyo-navi', $asset['license'] ?? null, $key);
            $this->assertGreaterThan(0, (int) ($asset['width'] ?? 0), $key);
            $this->assertGreaterThan(0, (int) ($asset['height'] ?? 0), $key);
            $this->assertStringNotContainsString('http://', $asset['path']);
            $this->assertStringNotContainsString('https://', $asset['path']);
        }
    }

    public function test_source_manifest_supports_core_legal_topics(): void
    {
        $blob = json_encode(IntersectionPriorityGuide::sources(), JSON_UNESCAPED_UNICODE);
        $this->assertIsString($blob);
        $this->assertStringContainsString('36', $blob);
        $this->assertStringContainsString('37', $blob);
        $this->assertStringContainsString('43', $blob);
        $this->assertStringContainsString('e-Gov', $blob);
        $this->assertStringContainsString('警察庁', $blob);
    }

    public function test_seo_metadata_and_article_json_ld_are_present(): void
    {
        $html = $this->get(route('learn.traffic-rules.intersection-priority'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString(__('intersection_priority.meta_title', [], 'ja'), $html);
        $this->assertStringContainsString(__('intersection_priority.meta_description', [], 'ja'), $html);
        $this->assertStringContainsString('application/ld+json', $html);
        $this->assertStringContainsString('"@type":"Article"', $html);
        $this->assertStringNotContainsString('aggregateRating', $html);
        $this->assertStringNotContainsString('hreflang', $html);
    }

    public function test_internal_discovery_links_point_to_the_guide(): void
    {
        $this->get(route('learn.traffic-rules'))
            ->assertSuccessful()
            ->assertSee(route('learn.traffic-rules.intersection-priority', absolute: false), false)
            ->assertSee(__('pages.traffic_rules.featured2_cta', [], 'ja'), false);

        $this->get(route('exams.karimen'))
            ->assertSuccessful()
            ->assertSee(route('learn.traffic-rules.intersection-priority', absolute: false), false);

        $this->get(route('exams.honmen'))
            ->assertSuccessful()
            ->assertSee(route('learn.traffic-rules.intersection-priority', absolute: false), false);

        $this->get(route('sitemap'))
            ->assertSuccessful()
            ->assertSee(route('learn.traffic-rules.intersection-priority'), false);
    }

    public function test_disabled_ads_do_not_render_on_the_guide(): void
    {
        config([
            'ads.enabled' => false,
            'ads.publisher_id' => null,
            'ads.layout_preview' => false,
        ]);

        $this->get(route('learn.traffic-rules.intersection-priority'))
            ->assertSuccessful()
            ->assertDontSee('data-ad-slot', false)
            ->assertDontSee('data-ad-preview', false)
            ->assertDontSee('data-ad-placement', false)
            ->assertDontSee('adsbygoogle', false)
            ->assertDontSee('googlesyndication', false);
    }

    public function test_guide_does_not_mutate_question_bank_tables(): void
    {
        $beforeQuestions = Schema::hasTable('questions') ? Question::query()->count() : 0;
        $beforeEntries = Schema::hasTable('question_import_entries')
            ? QuestionImportEntry::query()->count()
            : 0;

        $this->get(route('learn.traffic-rules.intersection-priority'))->assertSuccessful();

        if (Schema::hasTable('questions')) {
            $this->assertSame($beforeQuestions, Question::query()->count());
        }
        if (Schema::hasTable('question_import_entries')) {
            $this->assertSame($beforeEntries, QuestionImportEntry::query()->count());
        }
    }

    public function test_pedestrian_guide_still_renders_after_shared_base_refactor(): void
    {
        $this->get(route('learn.traffic-rules.pedestrian-priority'))
            ->assertSuccessful()
            ->assertSee(__('pedestrian_priority.title', [], 'ja'), false)
            ->assertSee(route('learn.traffic-rules.intersection-priority', absolute: false), false);
    }

    public function test_japanese_authoritative_content_remains_intact(): void
    {
        $ja = IntersectionPriorityGuide::content('ja');

        $this->assertSame('交差点の優先関係と一時停止のルール', $ja['title']);
        $this->assertStringContainsString('第36条', data_get($ja, 'fundamentals.p1'));
        $this->assertStringContainsString('第43条', data_get($ja, 'stop_signs.p1'));
        $this->assertStringContainsString('第37条', data_get($ja, 'turning.right_body'));
        $this->assertSame(
            ['stop', 'priority', 'left', 'right', 'hazard'],
            IntersectionPriorityGuide::scenarioIds('ja')
        );
        $this->assertFalse((bool) data_get(config('content.intersection_priority'), 'locales.ja.native_review'));
    }

    public function test_draft_locales_are_not_marked_native_reviewed_or_indexable(): void
    {
        foreach (['en', 'zh', 'vi', 'fil', 'ne', 'bn', 'ko'] as $locale) {
            $meta = data_get(config('content.intersection_priority'), "locales.{$locale}");
            $this->assertSame('draft_translation', $meta['status'] ?? null, $locale);
            $this->assertFalse((bool) ($meta['native_review'] ?? true), $locale);
            $this->assertFalse((bool) ($meta['indexable'] ?? true), $locale);
        }
    }

    public function test_interactive_controls_have_localized_labels_and_noscript_fallback(): void
    {
        $html = $this->withSession(['locale' => 'en'])
            ->get(route('learn.traffic-rules.intersection-priority'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString(__('intersection_priority.interaction_left', [], 'en'), $html);
        $this->assertStringContainsString(__('intersection_priority.interaction_right', [], 'en'), $html);
        $this->assertStringContainsString(__('intersection_priority.interaction_hazard_show', [], 'en'), $html);
        $this->assertStringContainsString('<noscript>', $html);
        $this->assertStringContainsString('noindex, follow', $html);
    }
}
