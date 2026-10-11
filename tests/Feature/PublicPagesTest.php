<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string}>
     */
    public static function publicRoutes(): array
    {
        return [
            'home' => ['/'],
            'exams' => ['/exams'],
            'gaimen' => ['/exams/gaimen-kirikae'],
            'karimen' => ['/exams/karimen'],
            'honmen' => ['/exams/honmen'],
            'learn' => ['/learn'],
            'road-signs' => ['/road-signs'],
            'traffic-rules' => ['/traffic-rules'],
            'hazard' => ['/hazard-prediction'],
            'guides' => ['/guides'],
            'conversion' => ['/guides/foreign-license-conversion'],
            'documents' => ['/guides/required-documents'],
            'exam-centers' => ['/exam-centers'],
            'about' => ['/about'],
            'contact' => ['/contact'],
            'privacy' => ['/privacy-policy'],
            'terms' => ['/terms-of-service'],
            'disclaimer' => ['/disclaimer'],
            'editorial' => ['/editorial-policy'],
            'sources' => ['/sources'],
            'faq' => ['/faq'],
            'cookies' => ['/cookie-policy'],
            'advertising' => ['/advertising-disclosure'],
            'sitemap' => ['/sitemap.xml'],
            'robots' => ['/robots.txt'],
        ];
    }

    #[DataProvider('publicRoutes')]
    public function test_public_pages_render_successfully(string $uri): void
    {
        $this->get($uri)->assertSuccessful();
    }

    public function test_home_page_contains_brand_and_primary_cta(): void
    {
        $this->get('/')
            ->assertSuccessful()
            ->assertSee('MenkyoNavi', false)
            ->assertSee('無料で学習を始める', false)
            ->assertSee('日本の運転免許試験に、自信を持って挑もう。', false)
            ->assertSee('免許ナビ', false)
            ->assertSee('images/logo.png', false);
    }

    public function test_exams_hub_highlights_three_exam_modes(): void
    {
        $this->withSession(['locale' => 'en'])
            ->get('/exams')
            ->assertSuccessful()
            ->assertSee('Gaimen kirikae', false)
            ->assertSee('Karimen', false)
            ->assertSee('Honmen', false)
            ->assertSee('外免切替', false)
            ->assertSee('仮免', false)
            ->assertSee('本免', false);
    }

    public function test_sitemap_excludes_private_paths(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertSuccessful();
        $response->assertDontSee('/dashboard');
        $response->assertDontSee('/login');
        $response->assertDontSee('/register');
    }

    public function test_robots_txt_disallows_private_areas(): void
    {
        $this->get('/robots.txt')
            ->assertSuccessful()
            ->assertSee('Disallow: /dashboard')
            ->assertSee('Disallow: /admin')
            ->assertSee('Sitemap:');
    }
}
