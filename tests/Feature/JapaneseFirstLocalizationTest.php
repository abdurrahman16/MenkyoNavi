<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JapaneseFirstLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_defaults_to_japanese_copy(): void
    {
        $this->get('/')
            ->assertSuccessful()
            ->assertSee('MenkyoNavi', false)
            ->assertSee('免許ナビ', false)
            ->assertSee('日本の運転免許試験に、自信を持って挑もう。', false)
            ->assertSee('無料で学習を始める', false)
            ->assertDontSee('Pass Your Japanese Driving License Exam with Confidence', false)
            ->assertDontSee('How it works', false);
    }

    public function test_switching_to_english_localizes_navigation_and_home(): void
    {
        $this->from('/')
            ->post(route('locale.switch', 'en'))
            ->assertRedirect('/');

        $this->get('/')
            ->assertSuccessful()
            ->assertSee('lang="en"', false)
            ->assertSee(__('home.hero_title', [], 'en'), false)
            ->assertSee(__('home.start_free_practice', [], 'en'), false)
            ->assertSee(__('navigation.exams', [], 'en'), false);
    }
}
