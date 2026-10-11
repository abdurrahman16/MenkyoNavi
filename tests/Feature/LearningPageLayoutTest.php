<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LearningPageLayoutTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function learningRoutes(): array
    {
        return [
            'traffic-rules' => ['/traffic-rules'],
            'road-signs' => ['/road-signs'],
            'hazard' => ['/hazard-prediction'],
            'learn' => ['/learn'],
            'study-methods' => ['/learn/study-methods'],
        ];
    }

    #[DataProvider('learningRoutes')]
    public function test_learning_pages_render_successfully(string $uri): void
    {
        $this->get($uri)->assertSuccessful();
    }

    public function test_traffic_rules_page_uses_shared_learning_layout_and_related_links(): void
    {
        $response = $this->get('/traffic-rules');

        $response->assertSuccessful();
        $response->assertSee(__('pages.traffic_rules.title', [], 'ja'), false);
        $response->assertSee(__('pages.traffic_rules.intro', [], 'ja'), false);
        $response->assertSee(__('pages.traffic_rules.link_signs', [], 'ja'), false);
        $response->assertSee(__('pages.traffic_rules.link_hazard', [], 'ja'), false);
        $response->assertSee(__('pages.traffic_rules.link_karimen', [], 'ja'), false);
        $response->assertSee(route('learn.road-signs', absolute: false), false);
        $response->assertSee(route('learn.hazard-prediction', absolute: false), false);
        $response->assertSee(route('exams.karimen', absolute: false), false);
        $response->assertSee('items-start', false);
        $response->assertSee('lg:col-span-8', false);
        $response->assertSee('lg:col-span-4', false);
        $response->assertSee('lg:self-start', false);
        $response->assertSee(__('pages.independent_title', [], 'ja'), false);
        $response->assertSee('</main>', false);
        $response->assertSee('<footer', false);
    }

    public function test_traffic_rules_footer_appears_outside_main_content(): void
    {
        $html = $this->get('/traffic-rules')->assertSuccessful()->getContent();

        $mainClose = strpos($html, '</main>');
        $footerOpen = strpos($html, '<footer');

        $this->assertNotFalse($mainClose);
        $this->assertNotFalse($footerOpen);
        $this->assertGreaterThan($mainClose, $footerOpen);
    }

    public function test_disabled_ads_do_not_render_blank_slots_on_traffic_rules(): void
    {
        config([
            'ads.enabled' => false,
            'ads.publisher_id' => null,
            'ads.layout_preview' => false,
        ]);

        $this->get('/traffic-rules')
            ->assertSuccessful()
            ->assertDontSee('data-ad-slot', false)
            ->assertDontSee('data-ad-preview', false)
            ->assertDontSee('data-ad-placement', false)
            ->assertDontSee('adsbygoogle', false)
            ->assertDontSee('googlesyndication', false);
    }

    public function test_traffic_rules_localizes_across_supported_locales(): void
    {
        foreach (['ja', 'en', 'zh', 'ko', 'vi', 'fil', 'ne', 'bn'] as $locale) {
            $this->withSession(['locale' => $locale])
                ->get('/traffic-rules')
                ->assertSuccessful()
                ->assertSee(__('pages.traffic_rules.title', [], $locale), false)
                ->assertSee(__('pages.traffic_rules.link_karimen', [], $locale), false);
        }
    }

    public function test_shared_learning_layout_component_defaults_to_content_sized_sidebar(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-layouts.learning sidebar-label="Related">
                Main article content
                <x-slot:sidebar>Sidebar resources</x-slot:sidebar>
            </x-layouts.learning>
        BLADE);

        $this->assertStringContainsString('items-start', $html);
        $this->assertStringContainsString('lg:self-start', $html);
        $this->assertStringContainsString('lg:col-span-8', $html);
        $this->assertStringContainsString('lg:col-span-4', $html);
        $this->assertStringNotContainsString('min-h-screen', $html);
        $this->assertStringNotContainsString('auto-rows-fr', $html);
    }
}
