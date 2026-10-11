<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\SupportedLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteLocalizationPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_japanese_is_the_default_locale_for_first_visit(): void
    {
        $this->get('/')
            ->assertSuccessful()
            ->assertSee('lang="ja"', false)
            ->assertSee(__('home.hero_title', [], 'ja'), false)
            ->assertSee(__('home.how_it_works_title', [], 'ja'), false)
            ->assertDontSee('How it works', false)
            ->assertDontSee('Learning resources', false)
            ->assertDontSee('Ready to start free practice?', false);
    }

    public function test_all_eight_supported_locales_can_be_selected(): void
    {
        foreach (SupportedLocale::codes() as $locale) {
            $this->from('/')
                ->post(route('locale.switch', $locale))
                ->assertRedirect('/');

            $this->get('/')
                ->assertSuccessful()
                ->assertSee('lang="'.$locale.'"', false)
                ->assertSee(__('home.hero_title', [], $locale), false);
        }
    }

    public function test_selected_locale_persists_across_pages(): void
    {
        $this->from('/')
            ->post(route('locale.switch', 'en'))
            ->assertRedirect('/');

        $this->get('/exams')
            ->assertSuccessful()
            ->assertSee('lang="en"', false)
            ->assertSee(__('pages.exams.title', [], 'en'), false);

        $this->get('/learn')
            ->assertSuccessful()
            ->assertSee(__('pages.learn.title', [], 'en'), false);
    }

    public function test_exams_hub_is_fully_japanese_when_ja_is_active(): void
    {
        $this->get('/exams')
            ->assertSuccessful()
            ->assertSee(__('pages.exams.title', [], 'ja'), false)
            ->assertSee(__('pages.exams.journey_title', [], 'ja'), false)
            ->assertSee(__('pages.exams.resources_title', [], 'ja'), false)
            ->assertDontSee('Choose your Japanese license exam path', false)
            ->assertDontSee('Your study journey', false)
            ->assertDontSee('Featured learning resources', false);
    }

    public function test_homepage_sample_preview_shows_single_language_copy(): void
    {
        $this->get('/')
            ->assertSuccessful()
            ->assertSee(__('home.sample_preview_label', [], 'ja'), false)
            ->assertSee(__('home.sample_question_text', [], 'ja'), false)
            ->assertDontSee('English translation', false)
            ->assertDontSee('Side-by-side Japanese and English preview', false)
            ->assertDontSee('Sample learning interface', false);

        $this->from('/')
            ->post(route('locale.switch', 'bn'))
            ->assertRedirect('/');

        $bnSample = __('home.sample_question_text', [], 'bn');
        $enSample = __('home.sample_question_text', [], 'en');

        $this->assertNotSame($enSample, $bnSample, 'Bangla home sample copy must be translated.');

        $this->get('/')
            ->assertSuccessful()
            ->assertSee($bnSample, false)
            ->assertDontSee($enSample, false);
    }

    public function test_contact_recipient_stays_private_and_tokyo_location_is_shown(): void
    {
        $this->assertSame('owner@menkyo-navi.test', config('menkyo.contact_recipient_email'));
        $this->assertSame('Tokyo, Japan', config('menkyo.operating_location.en'));
        $this->assertSame('日本・東京都', config('menkyo.operating_location.ja'));

        $this->get('/contact')
            ->assertSuccessful()
            ->assertDontSee('owner@menkyo-navi.test', false)
            ->assertSee(__('pages.location_value', [], 'ja'), false)
            ->assertSee(__('pages.contact.instructions', [], 'ja'), false);
    }

    public function test_contact_form_still_accepts_messages(): void
    {
        $response = $this->from(route('contact'))->post(route('contact.store'), [
            'name' => 'Test User',
            'email' => 'learner@example.com',
            'subject' => 'Localization check',
            'message' => 'Please confirm the contact form still works during localization work.',
        ]);

        $response->assertRedirect(route('contact'));
        $response->assertSessionHas('status');
    }

    public function test_advertisements_remain_disabled(): void
    {
        $this->assertFalse((bool) config('ads.enabled'));
        $this->assertTrue((bool) config('ads.require_consent'));

        $this->get('/')
            ->assertSuccessful()
            ->assertDontSee('adsbygoogle', false);
    }

    public function test_cookie_and_advertising_pages_are_localized(): void
    {
        $this->get('/cookie-policy')
            ->assertSuccessful()
            ->assertSee(__('pages.cookies.title', [], 'ja'), false);

        $this->get('/advertising-disclosure')
            ->assertSuccessful()
            ->assertSee(__('pages.advertising.title', [], 'ja'), false)
            ->assertSee('ADSENSE_ENABLED', false);
    }

    public function test_admin_dashboard_labels_follow_active_locale(): void
    {
        $admin = User::factory()->administrator()->create();

        $this->actingAs($admin)
            ->get(route('admin.questions.dashboard'))
            ->assertSuccessful()
            ->assertSee(__('admin.dashboard_title', [], 'ja'), false)
            ->assertSee(__('admin.translation_progress', [], 'ja'), false)
            ->assertDontSee('Question bank dashboard', false);
    }

    public function test_learning_pages_follow_active_locale_without_english_fallback_copy(): void
    {
        $this->get(route('learn.traffic-rules'))
            ->assertSuccessful()
            ->assertSee(__('pages.traffic_rules.title', [], 'ja'), false)
            ->assertDontSee('Practical explanations of how vehicles, pedestrians, and cyclists share Japan', false);

        $this->from('/')
            ->post(route('locale.switch', 'zh'))
            ->assertRedirect('/');

        $this->get(route('learn.road-signs'))
            ->assertSuccessful()
            ->assertSee(__('road_signs.title', [], 'zh'), false);
    }
}
