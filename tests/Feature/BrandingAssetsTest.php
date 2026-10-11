<?php

namespace Tests\Feature;

use Tests\TestCase;

class BrandingAssetsTest extends TestCase
{
    public function test_official_brand_assets_exist(): void
    {
        $this->assertFileExists(public_path('images/logo.png'));
        $this->assertFileExists(public_path('images/logo.original.png'));
        $this->assertFileExists(public_path('images/fav.png'));
        $this->assertFileExists(public_path('images/fav.original.png'));
        $this->assertFileExists(public_path('favicon.svg'));
        $this->assertFileExists(public_path('images/favicon-16.png'));
        $this->assertFileExists(public_path('images/favicon-32.png'));
        $this->assertFileExists(public_path('images/apple-touch-icon.png'));
    }

    public function test_homepage_uses_png_logo_and_favicon(): void
    {
        $this->get('/')
            ->assertSuccessful()
            ->assertSee('images/logo.png', false)
            ->assertSee('images/fav.png', false)
            ->assertSee('images/favicon-32.png', false)
            ->assertSee('images/favicon-16.png', false)
            ->assertSee('images/apple-touch-icon.png', false)
            ->assertSee('MenkyoNavi (免許ナビ) logo', false)
            ->assertSee('favicon.svg', false)
            ->assertSee('data-site-logo', false)
            ->assertSee('h-10 w-auto max-w-[12.75rem]', false);
    }

    public function test_header_logo_presentation_is_enlarged(): void
    {
        $html = $this->get('/')->assertSuccessful()->getContent();

        $this->assertStringContainsString('data-site-logo', $html);
        $this->assertStringContainsString('site-logo', $html);
        $this->assertMatchesRegularExpression('/h-10[^"]*max-w-\[12\.75rem\]/', $html);
        $this->assertDoesNotMatchRegularExpression('/data-site-logo[\s\S]{0,240}h-8 w-auto max-w-\[10\.5rem\]/', $html);
    }

    public function test_favicon_paths_resolve_to_real_assets(): void
    {
        $response = $this->get('/')->assertSuccessful();
        $html = $response->getContent();

        foreach ([
            'images/favicon-16.png',
            'images/favicon-32.png',
            'images/apple-touch-icon.png',
            'images/fav.png',
            'favicon.svg',
        ] as $path) {
            $this->assertStringContainsString($path, $html);
            $this->assertFileExists(public_path($path));
        }
    }
}
