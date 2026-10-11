<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\QuestionImportEntry;
use App\Support\Content\RoadSignsGuide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoadSignsGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_road_signs_route_renders_japanese_default_library(): void
    {
        $this->get(route('learn.road-signs'))
            ->assertSuccessful()
            ->assertSee(__('road_signs.title', [], 'ja'), false)
            ->assertSee(__('road_signs.intro', [], 'ja'), false)
            ->assertSee('<h1', false)
            ->assertSee('data-educational-guide', false)
            ->assertSee('data-road-signs-library', false)
            ->assertSee('data-sign-library', false)
            ->assertSee('data-guide-toc', false)
            ->assertSee('data-guide-sources', false)
            ->assertSee('data-educational-practice', false)
            ->assertSee('data-sign-recognition-practice', false)
            ->assertSee(__('road_signs.sources_heading', [], 'ja'), false)
            ->assertSee('https://laws.e-gov.go.jp/law/335M50004002003/', false)
            ->assertSee('https://www.mlit.go.jp/road/sign/sign/douro/ichiran.pdf', false)
            ->assertSee('一時停止', false)
            ->assertSee('車両進入禁止', false)
            ->assertSee('指示標識', false)
            ->assertSee('補助標識', false)
            ->assertSee(route('exams.karimen', absolute: false), false)
            ->assertSee(route('learn.traffic-rules.pedestrian-priority', absolute: false), false)
            ->assertSee('<noscript>', false)
            ->assertSee(__('road_signs.meta_description', [], 'ja'), false);
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
    public function test_eight_locales_serve_complete_road_sign_content(string $locale): void
    {
        $this->assertTrue(RoadSignsGuide::hasCompleteContent($locale));
        $this->assertFalse(RoadSignsGuide::hasEnglishPlaceholderBody($locale));

        $content = RoadSignsGuide::content($locale);
        $catalog = RoadSignsGuide::signCatalog();

        $this->assertCount(17, $catalog);
        $this->assertCount(17, $content['signs'] ?? []);

        foreach (array_keys($catalog) as $signId) {
            $this->assertArrayHasKey($signId, $content['signs']);
            foreach (['title', 'meaning_short', 'meaning', 'must_do', 'example', 'misconception', 'alt'] as $field) {
                $this->assertNotSame('', (string) data_get($content, "signs.{$signId}.{$field}"));
            }
            $this->assertNotEmpty(data_get($content, "signs.{$signId}.keywords", []));
        }

        $enriched = RoadSignsGuide::enrichedSigns($locale);
        $this->assertCount(17, $enriched);
        foreach ($enriched as $sign) {
            $this->assertNotSame('', $sign['official_name_ja']);
            $this->assertSame('verified', $sign['review_state']);
            $this->assertFileExists(public_path($sign['illustration']));
        }

        $practice = RoadSignsGuide::practiceItems($locale);
        $englishPractice = RoadSignsGuide::practiceItems('en');
        $this->assertCount(count($englishPractice), $practice);
        foreach ($practice as $index => $item) {
            $this->assertSame($englishPractice[$index]['sign_id'], $item['sign_id']);
            $this->assertSame($englishPractice[$index]['answer'], $item['answer']);
            $this->assertNotSame('', (string) ($item['q'] ?? ''));
            $this->assertNotSame('', (string) ($item['explain'] ?? ''));
            $this->assertCount(4, $item['choices'] ?? []);
        }

        foreach (data_get($content, 'visuals.alts', []) as $alt) {
            $this->assertNotSame('', (string) $alt);
        }
        foreach (data_get($content, 'visuals.captions', []) as $caption) {
            $this->assertNotSame('', (string) $caption);
        }

        $response = $this->withSession(['locale' => $locale])
            ->get(route('learn.road-signs'));

        $response->assertSuccessful()
            ->assertSee(__('road_signs.title', [], $locale), false)
            ->assertSee(__('road_signs.meta_description', [], $locale), false)
            ->assertSee(__('road_signs.toc_label', [], $locale), false)
            ->assertSee(__('road_signs.library_heading', [], $locale), false)
            ->assertSee(__('road_signs.faq_heading', [], $locale), false)
            ->assertSee('lang="'.$locale.'"', false)
            ->assertSee('一時停止', false);

        if ($locale !== 'ja') {
            $response->assertSee(__('road_signs.draft_notice', [], $locale), false);
        }

        if ($locale !== 'ja') {
            $this->assertFalse(RoadSignsGuide::isIndexable($locale));
            $this->assertSame('noindex, follow', RoadSignsGuide::robots($locale));
            $response->assertSee('noindex, follow', false);
        }

        if ($locale === 'ja') {
            $this->assertTrue(RoadSignsGuide::isIndexable($locale));
            $this->assertNull(RoadSignsGuide::robots($locale));
        }
    }

    public function test_localized_search_terms_resolve_core_signs(): void
    {
        $cases = [
            'ja' => ['止まれ', 'temporary-stop'],
            'en' => ['full stop', 'temporary-stop'],
            'zh' => ['停车', 'temporary-stop'],
            'vi' => ['dừng', 'temporary-stop'],
            'fil' => ['hinto', 'temporary-stop'],
            'ne' => ['रोक', 'temporary-stop'],
            'bn' => ['থাম', 'temporary-stop'],
            'ko' => ['정지', 'temporary-stop'],
        ];

        foreach ($cases as $locale => [$term, $expectedId]) {
            $matches = RoadSignsGuide::filterSigns(null, $term, $locale);
            $this->assertTrue(
                collect($matches)->contains(fn (array $sign): bool => $sign['id'] === $expectedId),
                "Expected {$expectedId} for locale {$locale} term {$term}"
            );
        }
    }

    public function test_official_categories_and_sign_structure_are_valid(): void
    {
        $catalog = RoadSignsGuide::signCatalog();
        $this->assertGreaterThanOrEqual(15, count($catalog));
        $this->assertLessThanOrEqual(20, count($catalog));

        $expectedCategories = RoadSignsGuide::CATEGORY_KEYS;
        $seen = [];

        foreach ($catalog as $id => $sign) {
            $this->assertSame($id, $sign['id'] ?? null);
            $this->assertNotSame('', $sign['official_name_ja'] ?? '');
            $this->assertNotSame('', $sign['code'] ?? '');
            $this->assertContains($sign['category'] ?? null, $expectedCategories);
            $this->assertSame('verified', $sign['review_state'] ?? null);
            $this->assertNotEmpty($sign['source_ids'] ?? []);
            $this->assertFileExists(public_path((string) $sign['illustration']));
            $seen[$sign['category']] = true;
        }

        foreach ($expectedCategories as $category) {
            $this->assertArrayHasKey($category, $seen, "Missing category {$category}");
        }

        $this->assertSame('regulatory', $catalog['temporary-stop']['category']);
        $this->assertSame('regulatory', $catalog['no-entry']['category']);
        $this->assertSame('regulatory', $catalog['one-way']['category']);
        $this->assertSame('warning', $catalog['railroad-crossing']['category']);
        $this->assertSame('instruction', $catalog['pedestrian-crossing']['category']);
        $this->assertSame('instruction', $catalog['priority-road']['category']);
        $this->assertSame('guide', $catalog['destination-guide']['category']);
        $this->assertSame('supplementary', $catalog['time-plate']['category']);
    }

    public function test_search_and_filter_behavior(): void
    {
        $filtered = RoadSignsGuide::filterSigns('regulatory', null, 'ja');
        $this->assertNotEmpty($filtered);
        foreach ($filtered as $sign) {
            $this->assertSame('regulatory', $sign['category']);
        }

        $search = RoadSignsGuide::filterSigns(null, '止まれ', 'ja');
        $this->assertTrue(collect($search)->contains(fn (array $sign): bool => $sign['id'] === 'temporary-stop'));

        $empty = RoadSignsGuide::filterSigns('guide', 'zzzz-no-match', 'ja');
        $this->assertSame([], $empty);

        $this->get(route('learn.road-signs', ['category' => 'warning', 'q' => '踏切']))
            ->assertSuccessful()
            ->assertSee('踏切あり', false);
    }

    public function test_practice_structure_and_accessible_fallback(): void
    {
        $items = RoadSignsGuide::practiceItems('ja');
        $this->assertGreaterThanOrEqual(6, count($items));
        $this->assertLessThanOrEqual(8, count($items));

        foreach ($items as $item) {
            $this->assertArrayHasKey('sign_id', $item);
            $this->assertArrayHasKey($item['sign_id'], RoadSignsGuide::signCatalog());
            $this->assertCount(4, $item['choices'] ?? []);
            $this->assertIsInt($item['answer'] ?? null);
            $this->assertArrayHasKey($item['answer'], $item['choices']);
            $this->assertNotSame('', $item['explain'] ?? '');
        }

        $html = $this->get(route('learn.road-signs'))->assertSuccessful()->getContent();
        $this->assertStringContainsString('data-sign-recognition-practice', $html);
        $this->assertStringContainsString('<noscript>', $html);
        $this->assertStringContainsString(__('road_signs.practice.noscript_note', [], 'ja'), $html);
        $this->assertStringNotContainsString('Mistake Vault', $html);
    }

    public function test_seo_metadata_sources_and_assets(): void
    {
        $html = $this->get(route('learn.road-signs'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString(__('road_signs.meta_title', [], 'ja'), $html);
        $this->assertStringContainsString(__('road_signs.meta_description', [], 'ja'), $html);
        $this->assertStringContainsString('application/ld+json', $html);
        $this->assertStringContainsString('"@type":"Article"', $html);
        $this->assertStringNotContainsString('aggregateRating', $html);

        $sources = RoadSignsGuide::sources();
        $this->assertNotEmpty($sources);
        foreach ($sources as $source) {
            $this->assertNotSame('', $source['title'] ?? '');
            $this->assertNotSame('', $source['organization'] ?? '');
            $this->assertNotSame('', $source['url'] ?? '');
            $this->assertNotSame('', $source['section'] ?? '');
            $this->assertNotSame('', $source['checked_at'] ?? '');
            $this->assertNotEmpty($source['supports'] ?? []);
        }

        foreach (RoadSignsGuide::assets() as $key => $asset) {
            $this->assertIsArray($asset, (string) $key);
            $this->assertFileExists(public_path((string) $asset['path']));
            $this->assertSame('project-created', $asset['origin'] ?? null);
        }
    }

    public function test_internal_hub_pages_link_to_road_signs_library(): void
    {
        $this->get(route('learn.index'))
            ->assertSuccessful()
            ->assertSee(route('learn.road-signs', absolute: false), false)
            ->assertSee(__('pages.learn.card_signs_title', [], 'ja'), false);

        $this->get(route('learn.traffic-rules'))
            ->assertSuccessful()
            ->assertSee(route('learn.road-signs', absolute: false), false);

        $this->get(route('exams.karimen'))
            ->assertSuccessful()
            ->assertSee(route('learn.road-signs', absolute: false), false);

        $this->get(route('sitemap'))
            ->assertSuccessful()
            ->assertSee(route('learn.road-signs'), false);
    }

    public function test_disabled_ads_do_not_render_on_road_signs_page(): void
    {
        config([
            'ads.enabled' => false,
            'ads.publisher_id' => null,
            'ads.layout_preview' => false,
        ]);

        $this->get(route('learn.road-signs'))
            ->assertSuccessful()
            ->assertDontSee('data-ad-slot', false)
            ->assertDontSee('data-ad-preview', false)
            ->assertDontSee('data-ad-placement', false)
            ->assertDontSee('adsbygoogle', false)
            ->assertDontSee('googlesyndication', false);
    }

    public function test_road_signs_page_uses_full_width_responsive_learning_layout(): void
    {
        $html = $this->get(route('learn.road-signs'))
            ->assertSuccessful()
            ->getContent();

        foreach (array_keys(RoadSignsGuide::signCatalog()) as $signId) {
            $this->assertStringContainsString('data-road-sign-card="'.$signId.'"', $html);
            $this->assertStringContainsString('id="sign-'.$signId.'"', $html);
        }
        $this->assertCount(17, RoadSignsGuide::signCatalog());
        $this->assertStringContainsString('data-road-signs-layout="full"', $html);
        $this->assertStringContainsString('data-sign-filters', $html);
        $this->assertStringContainsString('data-sign-grid', $html);
        $this->assertStringContainsString('data-sign-details', $html);
        $this->assertStringContainsString('data-sign-details-card', $html);
        $this->assertStringContainsString('data-sign-detail-body', $html);
        $this->assertStringContainsString('grid grid-cols-1 items-start gap-4 sm:grid-cols-2 xl:grid-cols-3', $html);
        $this->assertStringContainsString('data-road-sign-scenes', $html);
        $this->assertStringContainsString('grid gap-6 md:grid-cols-2', $html);
        $this->assertStringContainsString('data-sign-recognition-practice', $html);
        $this->assertStringContainsString('id="library"', $html);
        $this->assertStringContainsString('id="scenes"', $html);
        $this->assertStringContainsString('id="practice"', $html);
        $this->assertStringContainsString(__('road_signs.open_details', [], 'ja'), $html);
        $this->assertStringContainsString(__('road_signs.library_practice_button', [], 'ja'), $html);
        $this->assertStringNotContainsString('id="details"', $html);
        $this->assertSame(1, substr_count($html, 'data-sign-grid'));
        $this->assertSame(17, substr_count($html, 'data-sign-details-card'));

        $libraryStart = strpos($html, 'id="library"');
        $this->assertNotFalse($libraryStart);
        $libraryChunk = substr($html, $libraryStart, 1200);
        $this->assertStringNotContainsString('motion-reveal-up', $libraryChunk);
        $this->assertStringNotContainsString('data-reveal', $libraryChunk);
        $this->assertStringNotContainsString('lg:col-span-8', $libraryChunk);

        foreach (['all', 'guide', 'warning', 'regulatory', 'instruction', 'supplementary'] as $category) {
            $this->assertStringContainsString('value="'.$category.'"', $html);
        }

        $sample = RoadSignsGuide::enrichedSigns('ja')[0];
        $this->assertStringContainsString($sample['meaning'], $html);
        $this->assertStringContainsString($sample['must_do'], $html);
        $this->assertStringContainsString($sample['example'], $html);
    }

    public function test_guide_publication_does_not_mutate_question_bank_tables(): void
    {
        $beforeQuestions = Schema::hasTable('questions') ? Question::query()->count() : 0;
        $beforeEntries = Schema::hasTable('question_import_entries')
            ? QuestionImportEntry::query()->count()
            : 0;

        $this->get(route('learn.road-signs'))->assertSuccessful();
        RoadSignsGuide::filterSigns('regulatory', '止まれ', 'ja');

        if (Schema::hasTable('questions')) {
            $this->assertSame($beforeQuestions, Question::query()->count());
        }
        if (Schema::hasTable('question_import_entries')) {
            $this->assertSame($beforeEntries, QuestionImportEntry::query()->count());
        }
    }

    public function test_missing_translation_falls_back_to_japanese(): void
    {
        $this->assertFalse(RoadSignsGuide::hasCompleteContent('xx'));
        $content = RoadSignsGuide::content('xx');
        $this->assertSame(__('road_signs.title', [], 'ja'), $content['title']);
        $this->assertNull(RoadSignsGuide::jsonLd('xx'));
        $this->assertSame('noindex, follow', RoadSignsGuide::robots('xx'));
    }
}
