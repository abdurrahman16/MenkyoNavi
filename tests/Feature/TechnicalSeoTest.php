<?php

namespace Tests\Feature;

use App\Support\Content\IntersectionPriorityGuide;
use App\Support\Content\PedestrianPriorityGuide;
use App\Support\Content\RoadSignsGuide;
use App\Support\Seo\CanonicalUrl;
use App\Support\Seo\IndexingPolicy;
use App\Support\Seo\SitemapBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class TechnicalSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_robots_txt_returns_plain_text_with_configured_sitemap_url(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertStringNotContainsString('<html', $response->getContent());
        $response->assertSee('Sitemap: '.url('/sitemap.xml'), false);
        $response->assertSee('Disallow: /admin', false);
        $response->assertSee('Disallow: /exams/attempts', false);
        $response->assertDontSee('Disallow: /road-signs', false);
        $response->assertDontSee('Disallow: /traffic-rules', false);
        $response->assertDontSee('Disallow: /css', false);
        $response->assertDontSee('Disallow: /build', false);
    }

    public function test_static_robots_txt_does_not_conflict_with_laravel_route(): void
    {
        $this->assertFalse(File::exists(public_path('robots.txt')));
        $this->get('/robots.txt')->assertSuccessful();
    }

    public function test_non_indexable_environment_robots_disallows_all(): void
    {
        config(['menkyo.seo.allow_indexing' => false]);

        $this->get('/robots.txt')
            ->assertSuccessful()
            ->assertSee("User-agent: *\nDisallow: /", false)
            ->assertSee('Sitemap: '.url('/sitemap.xml'), false);
    }

    public function test_sitemap_xml_is_valid_absolute_and_deterministic(): void
    {
        $builder = app(SitemapBuilder::class);
        $entries = $builder->entries();
        $locs = array_column($entries, 'loc');

        $response = $this->get('/sitemap.xml');
        $xml = $response->getContent();

        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"', $xml);
        $this->assertSame($locs, array_values(array_unique($locs)));
        $this->assertSame($locs, collect($locs)->sort()->values()->all());

        foreach ($locs as $loc) {
            $this->assertStringStartsWith(rtrim((string) config('app.url'), '/'), $loc);
            $this->assertStringNotContainsString('?', $loc);
            $response->assertSee('<loc>'.$loc.'</loc>', false);
        }

        $this->assertStringNotContainsString('<changefreq>', $xml);
        $this->assertStringNotContainsString('<priority>', $xml);
        $this->assertStringNotContainsString(
            '<loc>'.route('about').'</loc>'."\n".'        <lastmod>',
            $xml,
        );
    }

    public function test_sitemap_includes_approved_public_educational_pages(): void
    {
        $xml = $this->get('/sitemap.xml')->assertSuccessful()->getContent();

        foreach ([
            CanonicalUrl::route('home'),
            CanonicalUrl::route('learn.index'),
            CanonicalUrl::route('learn.study-methods'),
            CanonicalUrl::route('learn.road-signs'),
            CanonicalUrl::route('learn.traffic-rules'),
            CanonicalUrl::route('learn.traffic-rules.pedestrian-priority'),
            CanonicalUrl::route('learn.traffic-rules.intersection-priority'),
            CanonicalUrl::route('exams.karimen'),
            CanonicalUrl::route('about'),
        ] as $url) {
            $this->assertStringContainsString('<loc>'.$url.'</loc>', $xml);
        }
    }

    public function test_sitemap_excludes_private_and_noindex_routes(): void
    {
        $xml = $this->get('/sitemap.xml')->assertSuccessful()->getContent();

        foreach ([
            '/dashboard',
            '/login',
            '/register',
            '/admin',
            '/exams/attempts',
            '/mistakes',
            '/privacy-policy',
            '/terms-of-service',
            '/password',
        ] as $path) {
            $this->assertStringNotContainsString($path, $xml);
        }
    }

    public function test_sitemap_excludes_unpublished_question_identifiers(): void
    {
        $xml = $this->get('/sitemap.xml')->assertSuccessful()->getContent();

        $this->assertStringNotContainsString('/questions/', $xml);
        $this->assertStringNotContainsString('question_id', $xml);
        $this->assertStringNotContainsString('karimen-test-1', $xml);
    }

    public function test_sitemap_guide_lastmod_uses_manifest_dates_only(): void
    {
        $entries = collect(app(SitemapBuilder::class)->entries())->keyBy('route');

        $this->assertSame(
            config('content.road_signs.updated_at'),
            $entries['learn.road-signs']['lastmod'] ?? null,
        );
        $this->assertSame(
            config('content.pedestrian_priority.updated_at'),
            $entries['learn.traffic-rules.pedestrian-priority']['lastmod'] ?? null,
        );
        $this->assertArrayHasKey('about', $entries);
        $this->assertNull($entries['about']['lastmod']);
    }

    public function test_canonical_urls_are_stable_and_not_duplicated(): void
    {
        foreach ([
            CanonicalUrl::route('home') => '/',
            CanonicalUrl::route('learn.road-signs') => '/road-signs',
            CanonicalUrl::route('learn.traffic-rules.pedestrian-priority') => '/traffic-rules/pedestrian-priority',
            CanonicalUrl::route('learn.traffic-rules.intersection-priority') => '/traffic-rules/intersection-priority',
            CanonicalUrl::route('exams.karimen') => '/exams/karimen',
            CanonicalUrl::route('about') => '/about',
        ] as $canonical => $uri) {
            $html = $this->get($uri)->assertSuccessful()->getContent();

            $this->assertSame(1, substr_count($html, 'rel="canonical"'));
            $this->assertStringContainsString('href="'.$canonical.'"', $html);
        }

        $this->assertSame(
            route('learn.road-signs'),
            CanonicalUrl::from(url('/road-signs').'?q=止まれ&utm_source=test'),
        );
    }

    public function test_home_and_guide_titles_and_descriptions_are_meaningful(): void
    {
        $home = $this->get('/')->assertSuccessful();
        $home->assertSee('日本の運転免許試験対策・交通ルール学習', false);
        $home->assertSee(__('home.meta_description'), false);

        $this->get('/road-signs')
            ->assertSuccessful()
            ->assertSee('日本の道路標識一覧と意味・学習ガイド | 免許ナビ', false)
            ->assertSee(__('road_signs.meta_description'), false);

        $this->get('/traffic-rules/pedestrian-priority')
            ->assertSuccessful()
            ->assertSee('横断歩道と歩行者優先の交通ルール | 免許ナビ', false);

        $this->get('/traffic-rules/intersection-priority')
            ->assertSuccessful()
            ->assertSee('交差点の優先関係と一時停止のルール | 免許ナビ', false);

        $this->get('/exams/karimen')
            ->assertSuccessful()
            ->assertSee('仮免許学科試験の学習と模擬練習 | 免許ナビ', false);
    }

    public function test_breadcrumbs_and_json_ld_match_routes(): void
    {
        $html = $this->get('/traffic-rules/pedestrian-priority')->assertSuccessful()->getContent();

        $this->assertStringContainsString(route('learn.index'), $html);
        $this->assertStringContainsString(route('learn.traffic-rules'), $html);
        $this->assertStringContainsString('"@type":"BreadcrumbList"', $html);
        $this->assertStringContainsString('"@type":"Article"', $html);
        $this->assertStringNotContainsString('hreflang', $html);

        $this->assertNotFalse(json_decode($this->extractFirstJsonLd($html), true));
    }

    public function test_japanese_guides_remain_indexable_while_drafts_are_noindex(): void
    {
        foreach ([RoadSignsGuide::class, PedestrianPriorityGuide::class, IntersectionPriorityGuide::class] as $guide) {
            $this->assertTrue($guide::isIndexable('ja'));
            $this->assertNull($guide::robots('ja'));

            foreach (array_keys(config('menkyo.locales')) as $locale) {
                if ($locale === 'ja') {
                    continue;
                }

                $this->assertFalse($guide::isIndexable($locale), "{$guide} {$locale}");
                $this->assertSame('noindex, follow', $guide::robots($locale));
            }
        }

        $this->withSession(['locale' => 'en'])
            ->get('/road-signs')
            ->assertSuccessful()
            ->assertSee('noindex, follow', false);

        $this->withSession(['locale' => 'ja'])
            ->get('/road-signs')
            ->assertSuccessful()
            ->assertSee('index, follow', false)
            ->assertDontSee('noindex, follow', false);
    }

    public function test_non_indexable_environment_forces_page_noindex(): void
    {
        config(['menkyo.seo.allow_indexing' => false]);

        $this->get('/')
            ->assertSuccessful()
            ->assertSee('noindex, follow', false);
    }

    public function test_search_console_verification_token_is_not_faked(): void
    {
        $this->assertNull(IndexingPolicy::googleSiteVerificationToken());

        $this->get('/')
            ->assertSuccessful()
            ->assertDontSee('google-site-verification', false);

        config(['menkyo.seo.google_site_verification' => 'real-token-from-operator']);

        $html = $this->get('/')->assertSuccessful()->getContent();
        $this->assertSame(1, substr_count($html, 'name="google-site-verification"'));
        $this->assertStringContainsString('content="real-token-from-operator"', $html);
    }

    public function test_adsense_remains_disabled_on_indexable_guides(): void
    {
        $this->assertFalse((bool) config('ads.enabled'));

        $this->get('/road-signs')
            ->assertSuccessful()
            ->assertDontSee('googlesyndication', false)
            ->assertDontSee('adsbygoogle', false);
    }

    private function extractFirstJsonLd(string $html): string
    {
        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches);

        return html_entity_decode($matches[1] ?? '', ENT_QUOTES | ENT_HTML5);

    }
}
