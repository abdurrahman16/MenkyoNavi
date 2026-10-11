<?php

namespace Tests\Feature;

use App\Support\Advertising;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdSenseArchitectureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string}>
     */
    public static function eligiblePublicPages(): array
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
            'faq' => ['/faq'],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function sensitivePages(): array
    {
        return [
            'contact' => ['/contact'],
            'login' => ['/login'],
            'register' => ['/register'],
            'privacy' => ['/privacy-policy'],
            'terms' => ['/terms-of-service'],
            'cookies' => ['/cookie-policy'],
            'advertising' => ['/advertising-disclosure'],
            'disclaimer' => ['/disclaimer'],
        ];
    }

    public function test_adsense_script_is_absent_when_disabled(): void
    {
        config([
            'ads.enabled' => false,
            'ads.publisher_id' => null,
            'ads.layout_preview' => false,
        ]);

        $this->get('/')
            ->assertSuccessful()
            ->assertDontSee('googlesyndication', false)
            ->assertDontSee('adsbygoogle', false)
            ->assertDontSee('data-adsense-auto', false)
            ->assertDontSee('data-ad-placement', false);
    }

    public function test_adsense_script_renders_once_when_enabled_with_valid_publisher(): void
    {
        config([
            'ads.enabled' => true,
            'ads.publisher_id' => 'pub-1234567890123456',
            'ads.auto_enabled' => true,
            'ads.manual_enabled' => false,
            'ads.require_consent' => false,
            'ads.consent_granted' => true,
            'ads.layout_preview' => false,
        ]);

        $html = $this->get('/')->assertSuccessful()->getContent();

        $this->assertSame(1, substr_count($html, 'pagead2.googlesyndication.com/pagead/js/adsbygoogle.js'));
        $this->assertSame(1, substr_count($html, 'data-adsense-auto="true"'));
        $this->assertStringContainsString('client=ca-pub-1234567890123456', $html);
        $this->assertStringNotContainsString('pub-XXXXXXXXXXXXXXXX', $html);
    }

    public function test_no_fake_publisher_id_is_accepted_by_support_helper(): void
    {
        config(['ads.publisher_id' => 'pub-XXXXXXXXXXXXXXXX']);
        $this->assertNull(Advertising::clientId());

        config(['ads.publisher_id' => 'ca-pub-not-a-real-id']);
        $this->assertNull(Advertising::clientId());

        config(['ads.publisher_id' => 'pub-1234567890123456']);
        $this->assertSame('ca-pub-1234567890123456', Advertising::clientId());
    }

    #[DataProvider('eligiblePublicPages')]
    public function test_eligible_public_pages_can_host_auto_ads_script(string $uri): void
    {
        config([
            'ads.enabled' => true,
            'ads.publisher_id' => 'pub-1234567890123456',
            'ads.auto_enabled' => true,
            'ads.require_consent' => false,
            'ads.layout_preview' => false,
        ]);

        $this->get($uri)
            ->assertSuccessful()
            ->assertSee('data-adsense-auto="true"', false);
    }

    #[DataProvider('sensitivePages')]
    public function test_sensitive_pages_exclude_adsense_script(string $uri): void
    {
        config([
            'ads.enabled' => true,
            'ads.publisher_id' => 'pub-1234567890123456',
            'ads.auto_enabled' => true,
            'ads.require_consent' => false,
            'ads.layout_preview' => false,
        ]);

        $this->get($uri)
            ->assertSuccessful()
            ->assertDontSee('googlesyndication', false)
            ->assertDontSee('data-adsense-auto', false);
    }

    public function test_disabled_ads_do_not_create_blank_layout_containers(): void
    {
        config([
            'ads.enabled' => false,
            'ads.publisher_id' => null,
            'ads.layout_preview' => false,
        ]);

        foreach (['/', '/learn', '/traffic-rules', '/guides'] as $uri) {
            $this->get($uri)
                ->assertSuccessful()
                ->assertDontSee('data-ad-placement', false)
                ->assertDontSee('data-ad-preview', false);
        }
    }

    public function test_manual_slots_require_unit_ids_and_do_not_render_empty_shells(): void
    {
        config([
            'ads.enabled' => true,
            'ads.publisher_id' => 'pub-1234567890123456',
            'ads.auto_enabled' => true,
            'ads.manual_enabled' => true,
            'ads.require_consent' => false,
            'ads.layout_preview' => false,
            'ads.slots.content-mid.ad_unit_id' => null,
            'ads.slots.content-bottom.ad_unit_id' => null,
        ]);

        $this->get('/')
            ->assertSuccessful()
            ->assertSee('data-adsense-auto="true"', false)
            ->assertDontSee('data-ad-placement', false)
            ->assertDontSee('data-ad-slot=', false);
    }

    public function test_exam_control_slots_remain_disabled_by_default(): void
    {
        $this->assertFalse((bool) config('ads.slots.exam-left.enabled'));
        $this->assertFalse((bool) config('ads.slots.exam-right.enabled'));
        $this->assertFalse((bool) config('ads.slots.exam-bottom.enabled'));
    }

    public function test_ads_txt_is_not_served_without_real_publisher_configuration(): void
    {
        config([
            'ads.enabled' => false,
            'ads.publisher_id' => null,
        ]);

        $this->get('/ads.txt')->assertNotFound();
    }

    public function test_ads_txt_is_served_with_valid_publisher_even_when_live_ads_disabled(): void
    {
        config([
            'ads.enabled' => false,
            'ads.publisher_id' => 'ca-pub-1234567890123456',
            'ads.require_consent' => true,
            'ads.consent_granted' => false,
        ]);

        $this->get('/ads.txt')
            ->assertSuccessful()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('google.com, pub-1234567890123456, DIRECT, f08c47fec0942fa0', false)
            ->assertDontSee('XXXXXXXX', false);

        $this->get('/')
            ->assertSuccessful()
            ->assertDontSee('googlesyndication', false)
            ->assertDontSee('data-adsense-auto', false);
    }

    public function test_ads_txt_is_served_when_enabled_with_valid_publisher(): void
    {
        config([
            'ads.enabled' => true,
            'ads.publisher_id' => 'ca-pub-1234567890123456',
        ]);

        $this->get('/ads.txt')
            ->assertSuccessful()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('google.com, pub-1234567890123456, DIRECT, f08c47fec0942fa0', false)
            ->assertDontSee('XXXXXXXX', false);
    }
}
