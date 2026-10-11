<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class JapaneseUiMotionPresentationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string}>
     */
    public static function publicUiRoutes(): array
    {
        return [
            'home' => ['/'],
            'exams' => ['/exams'],
            'learn' => ['/learn'],
            'traffic-rules' => ['/traffic-rules'],
            'road-signs' => ['/road-signs'],
            'hazard' => ['/hazard-prediction'],
            'guides' => ['/guides'],
        ];
    }

    #[DataProvider('publicUiRoutes')]
    public function test_public_pages_render_with_japanese_design_shell(string $uri): void
    {
        $this->get($uri)
            ->assertSuccessful()
            ->assertSee('theme-color" content="#26466D"', false)
            ->assertSee('bg-surface', false)
            ->assertSee('</main>', false)
            ->assertSee('<footer', false);
    }

    public function test_homepage_keeps_japanese_first_messaging_and_single_language_sample(): void
    {
        $this->get('/')
            ->assertSuccessful()
            ->assertSee(__('home.hero_title', [], 'ja'), false)
            ->assertSee(__('home.start_free_practice', [], 'ja'), false)
            ->assertSee(__('home.sample_true', [], 'ja'), false)
            ->assertSee(__('home.sample_false', [], 'ja'), false)
            ->assertSee('hero-animate', false)
            ->assertSee('hero-title-mask', false)
            ->assertSee('hero-title-inner', false)
            ->assertSee('svg-draw-road', false)
            ->assertSee('data-hero-visual', false)
            ->assertSee('motion-stagger-group', false)
            ->assertSee('motion-reveal-scale', false)
            ->assertSee('journey-track', false)
            ->assertSee('seigaiha-layer', false)
            ->assertSee('data-parallax-layer', false)
            ->assertSee('data-reveal', false)
            ->assertDontSee('English / Japanese', false);
    }

    public function test_public_layout_enables_safe_view_transition_meta(): void
    {
        $this->get('/')
            ->assertSuccessful()
            ->assertSee('name="view-transition" content="same-origin"', false);

        $this->get('/login')
            ->assertSuccessful()
            ->assertDontSee('name="view-transition" content="same-origin"', false);
    }

    public function test_learning_pages_preserve_sidebar_layout_structure(): void
    {
        $html = $this->get('/traffic-rules')->assertSuccessful()->getContent();

        $this->assertStringContainsString('items-start', $html);
        $this->assertStringContainsString('lg:self-start', $html);
        $this->assertStringContainsString('lg:col-span-8', $html);
        $this->assertStringContainsString('lg:col-span-4', $html);

        $mainClose = strpos($html, '</main>');
        $footerOpen = strpos($html, '<footer');
        $this->assertNotFalse($mainClose);
        $this->assertNotFalse($footerOpen);
        $this->assertGreaterThan($mainClose, $footerOpen);
    }

    public function test_motion_stylesheet_includes_reduced_motion_and_no_js_safe_reveals(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertIsString($css);
        $this->assertStringContainsString('prefers-reduced-motion: reduce', $css);
        $this->assertStringContainsString('html.js-motion:not(.reduce-motion) .reveal:not(.is-revealed)', $css);
        $this->assertStringContainsString('html.js-motion:not(.reduce-motion) .motion-reveal-up:not(.is-revealed)', $css);
        $this->assertStringContainsString('html.js-motion:not(.reduce-motion) .motion-reveal-left:not(.is-revealed)', $css);
        $this->assertStringContainsString('html.js-motion:not(.reduce-motion) .motion-reveal-right:not(.is-revealed)', $css);
        $this->assertStringContainsString('html.js-motion:not(.reduce-motion) .motion-reveal-scale:not(.is-revealed)', $css);
        $this->assertStringContainsString('html.js-motion:not(.reduce-motion) .motion-reveal-mask:not(.is-revealed)', $css);
        $this->assertStringContainsString('.svg-draw-road', $css);
        $this->assertStringContainsString('.motion-divider', $css);
        $this->assertStringContainsString('.result-ring', $css);
        $this->assertStringContainsString('@property --result-progress', $css);
        $this->assertStringContainsString('.reveal,', $css);
        $this->assertStringContainsString('opacity: 1', $css);
        $this->assertStringContainsString('--color-aizome: #26466D', $css);
        $this->assertStringContainsString('--color-vermilion: #C6533E', $css);
        $this->assertStringContainsString('--color-matcha: #71865A', $css);
        $this->assertStringContainsString('--color-washi: #F7F3E9', $css);
    }

    public function test_motion_script_does_not_intercept_navigation(): void
    {
        $js = file_get_contents(resource_path('js/motion.js'));

        $this->assertIsString($js);
        $this->assertStringContainsString('IntersectionObserver', $js);
        $this->assertStringContainsString('prefers-reduced-motion', $js);
        $this->assertStringContainsString('initHeroVisual', $js);
        $this->assertStringContainsString('initResultRings', $js);
        $this->assertStringContainsString('data-result-ring', $js);
        $this->assertStringNotContainsString('startViewTransition', $js);
        $this->assertStringNotContainsString('preventDefault', $js);
        $this->assertStringNotContainsString('window.location.href', $js);
    }

    public function test_tailwind_palette_uses_japanese_design_tokens(): void
    {
        $config = file_get_contents(base_path('tailwind.config.js'));

        $this->assertIsString($config);
        $this->assertStringContainsString('#26466D', $config);
        $this->assertStringContainsString('#172D46', $config);
        $this->assertStringContainsString('#F7F3E9', $config);
        $this->assertStringContainsString('#C6533E', $config);
        $this->assertStringContainsString('#71865A', $config);
        $this->assertStringContainsString('#B88A44', $config);
        $this->assertStringContainsString('#252A2D', $config);
    }

    public function test_disabled_ads_remain_absent_on_redesigned_homepage(): void
    {
        config([
            'ads.enabled' => false,
            'ads.publisher_id' => null,
            'ads.layout_preview' => false,
        ]);

        $this->get('/')
            ->assertSuccessful()
            ->assertDontSee('data-ad-placement', false)
            ->assertDontSee('adsbygoogle', false);
    }

    public function test_language_switching_still_works_on_homepage(): void
    {
        $this->withSession(['locale' => 'en'])
            ->get('/')
            ->assertSuccessful()
            ->assertSee(__('home.hero_title', [], 'en'), false)
            ->assertSee(__('home.start_free_practice', [], 'en'), false);
    }
}
