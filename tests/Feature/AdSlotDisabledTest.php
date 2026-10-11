<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdSlotDisabledTest extends TestCase
{
    public function test_homepage_does_not_render_ad_placeholders_when_disabled(): void
    {
        config([
            'ads.enabled' => false,
            'ads.publisher_id' => null,
            'ads.layout_preview' => false,
        ]);

        $this->get('/')
            ->assertSuccessful()
            ->assertDontSee('Advertisement', false)
            ->assertDontSee('data-ad-slot', false)
            ->assertDontSee('data-ad-preview', false)
            ->assertDontSee('googlesyndication', false)
            ->assertDontSee('adsbygoogle', false);
    }

    public function test_enabled_without_publisher_id_still_hides_slots(): void
    {
        config([
            'ads.enabled' => true,
            'ads.publisher_id' => null,
            'ads.layout_preview' => false,
            'ads.require_consent' => false,
        ]);

        $this->get('/')
            ->assertSuccessful()
            ->assertDontSee('data-ad-slot', false)
            ->assertDontSee('Advertisement', false);
    }

    public function test_learning_pages_do_not_render_blank_ad_containers_when_disabled(): void
    {
        config([
            'ads.enabled' => false,
            'ads.publisher_id' => null,
            'ads.layout_preview' => false,
        ]);

        foreach (['/traffic-rules', '/road-signs', '/hazard-prediction', '/learn'] as $uri) {
            $this->get($uri)
                ->assertSuccessful()
                ->assertDontSee('data-ad-placement', false)
                ->assertDontSee('data-ad-preview', false)
                ->assertDontSee('reserved_ad_space', false);
        }
    }
}
