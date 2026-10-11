<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\QuestionImportEntry;
use App\Support\Content\PedestrianPriorityGuide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PedestrianPriorityGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_guide_route_renders_japanese_default_content(): void
    {
        $this->get(route('learn.traffic-rules.pedestrian-priority'))
            ->assertSuccessful()
            ->assertSee(__('pedestrian_priority.title', [], 'ja'), false)
            ->assertSee(__('pedestrian_priority.intro', [], 'ja'), false)
            ->assertSee('<h1', false)
            ->assertSee('data-educational-guide', false)
            ->assertSee('data-guide-toc', false)
            ->assertSee('data-guide-sources', false)
            ->assertSee('data-educational-practice', false)
            ->assertSee(__('pedestrian_priority.sources_heading', [], 'ja'), false)
            ->assertSee('https://www.npa.go.jp/bureau/traffic/oudanhodou/info.html', false)
            ->assertSee('https://laws.e-gov.go.jp/law/335AC0000000105', false)
            ->assertSee(route('exams.karimen', absolute: false), false)
            ->assertSee(route('learn.traffic-rules', absolute: false), false)
            ->assertSee('viewport', false)
            ->assertSee(__('pedestrian_priority.meta_description', [], 'ja'), false);
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
        $this->assertTrue(PedestrianPriorityGuide::hasCompleteContent($locale));
        $this->assertFalse(PedestrianPriorityGuide::hasEnglishPlaceholderBody($locale));
        $this->assertFileExists(lang_path($locale.'/pedestrian_priority.php'));

        $content = PedestrianPriorityGuide::content($locale);
        $this->assertNotSame('', (string) data_get($content, 'visuals.alts.approach'));
        $this->assertNotSame('', (string) data_get($content, 'visuals.captions.approach'));
        $this->assertNotSame('', (string) data_get($content, 'meta_title'));
        $this->assertNotSame('', (string) data_get($content, 'meta_description'));
        $this->assertCount(3, data_get($content, 'scenarios.items', []));
        $this->assertCount(3, data_get($content, 'practice.items', []));
        $this->assertSame(
            PedestrianPriorityGuide::practiceAnswerMap('ja'),
            PedestrianPriorityGuide::practiceAnswerMap($locale)
        );

        $response = $this->withSession(['locale' => $locale])
            ->get(route('learn.traffic-rules.pedestrian-priority'));

        $response->assertSuccessful()
            ->assertSee(__('pedestrian_priority.title', [], $locale), false)
            ->assertSee(__('pedestrian_priority.toc_label', [], $locale), false)
            ->assertSee(__('pedestrian_priority.faq_heading', [], $locale), false)
            ->assertSee(__('pedestrian_priority.visuals.alts.approach', [], $locale), false)
            ->assertSee('lang="'.$locale.'"', false)
            ->assertSee('data-educational-practice', false);

        if ($locale !== 'ja') {
            $response->assertSee(__('pedestrian_priority.draft_notice', [], $locale), false);
            $this->assertSame('draft_translation', PedestrianPriorityGuide::localeStatus($locale));
            $this->assertFalse(PedestrianPriorityGuide::isIndexable($locale));
            $this->assertSame('noindex, follow', PedestrianPriorityGuide::robots($locale));
            $response->assertSee('noindex, follow', false);
            $this->assertNull(PedestrianPriorityGuide::jsonLd($locale));
        }

        if ($locale === 'ja') {
            $this->assertSame('authored', PedestrianPriorityGuide::localeStatus($locale));
            $this->assertTrue(PedestrianPriorityGuide::isIndexable($locale));
            $this->assertNull(PedestrianPriorityGuide::robots($locale));
            $this->assertNotNull(PedestrianPriorityGuide::jsonLd($locale));
        }
    }

    public function test_missing_translation_falls_back_to_japanese_without_claiming_native_review(): void
    {
        $this->assertFalse(PedestrianPriorityGuide::hasCompleteContent('xx'));

        $content = PedestrianPriorityGuide::content('xx');
        $this->assertSame(__('pedestrian_priority.title', [], 'ja'), $content['title']);
        $this->assertNull(PedestrianPriorityGuide::jsonLd('xx'));
        $this->assertSame('noindex, follow', PedestrianPriorityGuide::robots('xx'));
    }

    public function test_guide_includes_seo_metadata_and_article_json_ld_for_japanese(): void
    {
        $html = $this->get(route('learn.traffic-rules.pedestrian-priority'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString(__('pedestrian_priority.meta_title', [], 'ja'), $html);
        $this->assertStringContainsString(__('pedestrian_priority.meta_description', [], 'ja'), $html);
        $this->assertStringContainsString('application/ld+json', $html);
        $this->assertStringContainsString('"@type":"Article"', $html);
        $this->assertStringNotContainsString('aggregateRating', $html);
        $this->assertStringNotContainsString('hreflang', $html);
    }

    public function test_internal_hub_pages_link_to_the_guide(): void
    {
        $this->get(route('learn.traffic-rules'))
            ->assertSuccessful()
            ->assertSee(route('learn.traffic-rules.pedestrian-priority', absolute: false), false)
            ->assertSee(__('pages.traffic_rules.featured_cta', [], 'ja'), false);

        $this->get(route('learn.index'))
            ->assertSuccessful()
            ->assertSee(route('learn.traffic-rules.pedestrian-priority', absolute: false), false);

        $this->get(route('home'))
            ->assertSuccessful()
            ->assertSee(route('learn.traffic-rules.pedestrian-priority', absolute: false), false);

        $this->get(route('exams.karimen'))
            ->assertSuccessful()
            ->assertSee(route('learn.traffic-rules.pedestrian-priority', absolute: false), false);

        $this->get(route('exams.honmen'))
            ->assertSuccessful()
            ->assertSee(route('learn.traffic-rules.pedestrian-priority', absolute: false), false);
    }

    public function test_sitemap_includes_the_guide_route(): void
    {
        $this->get(route('sitemap'))
            ->assertSuccessful()
            ->assertSee(route('learn.traffic-rules.pedestrian-priority'), false);
    }

    public function test_disabled_ads_do_not_render_empty_slots_on_the_guide(): void
    {
        config([
            'ads.enabled' => false,
            'ads.publisher_id' => null,
            'ads.layout_preview' => false,
        ]);

        $this->get(route('learn.traffic-rules.pedestrian-priority'))
            ->assertSuccessful()
            ->assertDontSee('data-ad-slot', false)
            ->assertDontSee('data-ad-preview', false)
            ->assertDontSee('data-ad-placement', false)
            ->assertDontSee('adsbygoogle', false)
            ->assertDontSee('googlesyndication', false);
    }

    public function test_source_manifest_lists_verified_references(): void
    {
        $sources = PedestrianPriorityGuide::sources();

        $this->assertNotEmpty($sources);
        foreach ($sources as $source) {
            $this->assertNotSame('', $source['title'] ?? '');
            $this->assertNotSame('', $source['organization'] ?? '');
            $this->assertNotSame('', $source['url'] ?? '');
            $this->assertNotSame('', $source['section'] ?? '');
            $this->assertNotSame('', $source['checked_at'] ?? '');
            $this->assertNotEmpty($source['supports'] ?? []);
        }
    }

    public function test_guide_publication_does_not_mutate_question_bank_tables(): void
    {
        $beforeQuestions = Schema::hasTable('questions') ? Question::query()->count() : 0;
        $beforeEntries = Schema::hasTable('question_import_entries')
            ? QuestionImportEntry::query()->count()
            : 0;

        $this->get(route('learn.traffic-rules.pedestrian-priority'))->assertSuccessful();

        if (Schema::hasTable('questions')) {
            $this->assertSame($beforeQuestions, Question::query()->count());
        }
        if (Schema::hasTable('question_import_entries')) {
            $this->assertSame($beforeEntries, QuestionImportEntry::query()->count());
        }
    }

    public function test_diagram_assets_exist(): void
    {
        foreach (config('content.pedestrian_priority.diagrams') as $path) {
            $this->assertFileExists(public_path($path));
        }
    }

    public function test_japanese_authoritative_content_remains_intact(): void
    {
        $ja = PedestrianPriorityGuide::content('ja');

        $this->assertSame('歩行者優先と横断歩道の交通ルール', $ja['title']);
        $this->assertStringContainsString('第38条', data_get($ja, 'meaning.p1'));
        $this->assertStringContainsString('第38条の2', data_get($ja, 'unsignalized.p1'));
        $this->assertSame([1, 1, 1], PedestrianPriorityGuide::practiceAnswerMap('ja'));
        $this->assertFalse((bool) data_get(config('content.pedestrian_priority'), 'locales.ja.native_review'));
    }

    public function test_draft_locales_are_not_marked_native_reviewed(): void
    {
        foreach (['en', 'zh', 'vi', 'fil', 'ne', 'bn', 'ko'] as $locale) {
            $meta = data_get(config('content.pedestrian_priority'), "locales.{$locale}");
            $this->assertSame('draft_translation', $meta['status'] ?? null, $locale);
            $this->assertFalse((bool) ($meta['native_review'] ?? true), $locale);
            $this->assertFalse((bool) ($meta['indexable'] ?? true), $locale);
        }
    }

    public function test_non_javascript_practice_fallback_remains_readable(): void
    {
        $html = $this->get(route('learn.traffic-rules.pedestrian-priority'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('data-educational-practice', $html);
        $this->assertStringContainsString(__('pedestrian_priority.practice.items.0.explain', [], 'ja'), $html);
    }
}
