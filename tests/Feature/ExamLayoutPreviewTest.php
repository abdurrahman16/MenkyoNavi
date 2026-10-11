<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExamLayoutPreviewTest extends TestCase
{
    public function test_layout_preview_is_unavailable_when_disabled(): void
    {
        config(['ads.layout_preview' => false]);

        $this->get(route('exams.layout-preview'))->assertNotFound();
    }

    public function test_layout_preview_renders_placeholder_regions_without_adsense_scripts(): void
    {
        config([
            'ads.layout_preview' => true,
            'ads.enabled' => false,
            'ads.publisher_id' => null,
        ]);

        $this->get(route('exams.layout-preview'))
            ->assertSuccessful()
            ->assertSee('Sample examination panel', false)
            ->assertSee(__('common.layout_preview_ad', [], 'ja'), false)
            ->assertSee('data-ad-preview="true"', false)
            ->assertSee('exam-left', false)
            ->assertSee('exam-right', false)
            ->assertSee('exam-bottom', false)
            ->assertDontSee('googlesyndication', false)
            ->assertDontSee('adsbygoogle', false)
            ->assertDontSee('data-ad-client', false);
    }
}
