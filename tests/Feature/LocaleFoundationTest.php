<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\LocalePreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LocaleFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_configured_locales_include_eight_canonical_codes_with_japanese_default(): void
    {
        $locales = config('menkyo.locales');

        $this->assertSame(
            ['ja', 'en', 'zh', 'vi', 'fil', 'ne', 'bn', 'ko'],
            array_keys($locales)
        );
        $this->assertSame('ja', config('menkyo.default_locale'));
        $this->assertTrue($locales['ja']['enabled']);
        $this->assertTrue($locales['en']['enabled']);
        $this->assertSame('fil', config('menkyo.locale_aliases.tl'));
    }

    public function test_first_visit_defaults_to_japanese(): void
    {
        $this->get('/')
            ->assertSuccessful()
            ->assertSee('lang="ja"', false)
            ->assertSee(__('home.hero_title', [], 'ja'), false);

        $this->assertSame('ja', app()->getLocale());
        $this->assertSame('ja', session('locale'));
    }

    public function test_language_switcher_lists_all_supported_native_names(): void
    {
        $this->get('/')
            ->assertSuccessful()
            ->assertSee('English', false)
            ->assertSee('日本語', false)
            ->assertSee('简体中文', false)
            ->assertSee('Tiếng Việt', false)
            ->assertSee('Filipino', false)
            ->assertSee('नेपाली', false)
            ->assertSee('বাংলা', false)
            ->assertSee('한국어', false);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function supportedLocales(): array
    {
        return [
            'ja' => ['ja'],
            'en' => ['en'],
            'zh' => ['zh'],
            'vi' => ['vi'],
            'fil' => ['fil'],
            'ne' => ['ne'],
            'bn' => ['bn'],
            'ko' => ['ko'],
        ];
    }

    #[DataProvider('supportedLocales')]
    public function test_each_supported_locale_can_be_selected(string $locale): void
    {
        $this->from('/')
            ->post(route('locale.switch', $locale))
            ->assertRedirect('/')
            ->assertCookie(LocalePreference::COOKIE_NAME, $locale);

        $this->assertSame($locale, session('locale'));

        $this->get('/')->assertSuccessful();
        $this->assertSame($locale, app()->getLocale());
    }

    public function test_legacy_tl_locale_maps_to_filipino_preference(): void
    {
        $this->from('/')
            ->post(route('locale.switch', 'tl'))
            ->assertRedirect('/')
            ->assertCookie(LocalePreference::COOKIE_NAME, 'fil');

        $this->assertSame('fil', session('locale'));
    }

    public function test_invalid_locale_is_rejected(): void
    {
        $this->from('/')
            ->post(route('locale.switch', 'xx'))
            ->assertNotFound();
    }

    public function test_guest_locale_preference_persists_via_session_and_cookie(): void
    {
        $this->from('/')
            ->post(route('locale.switch', 'ko'))
            ->assertRedirect('/');

        $this->withCookie(LocalePreference::COOKIE_NAME, 'ko')
            ->get('/')
            ->assertSuccessful();

        $this->assertSame('ko', app()->getLocale());
    }

    public function test_authenticated_user_locale_preference_is_stored_on_profile(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'preferred_locale' => null,
        ]);

        $this->actingAs($user)
            ->from('/')
            ->post(route('locale.switch', 'vi'))
            ->assertRedirect('/');

        $this->assertSame('vi', $user->fresh()->preferred_locale);
        $this->assertSame('vi', session('locale'));
    }

    public function test_authenticated_user_preferred_locale_is_restored_without_session(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'preferred_locale' => 'bn',
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertSuccessful();

        $this->assertSame('bn', app()->getLocale());
    }

    public function test_seo_html_lang_follows_selected_locale(): void
    {
        $this->withSession(['locale' => 'en'])
            ->get('/')
            ->assertSuccessful()
            ->assertSee('lang="en"', false);
    }
}
