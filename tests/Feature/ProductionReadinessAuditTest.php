<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ProductionReadinessAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_sensitive_storage_exports_are_not_publicly_downloadable(): void
    {
        $exportRelative = 'menkyo-exports/production-readiness-probe.txt';
        $exportPath = storage_path('app/'.$exportRelative);

        File::ensureDirectoryExists(dirname($exportPath));
        File::put($exportPath, 'secret-export-marker');

        try {
            foreach (['/storage/'.$exportRelative, '/storage/app/'.$exportRelative, '/'.$exportRelative] as $uri) {
                $status = $this->get($uri)->status();
                $this->assertContains($status, [403, 404], "Unexpected public access for {$uri}");
            }
        } finally {
            File::delete($exportPath);
        }
    }

    public function test_static_robots_file_does_not_override_dynamic_controller(): void
    {
        $this->assertFalse(
            File::exists(public_path('robots.txt')),
            'A static public/robots.txt would bypass RobotsController on many hosts.'
        );

        $this->get('/robots.txt')
            ->assertSuccessful()
            ->assertSee('Disallow: /admin', false)
            ->assertSee('Disallow: /dashboard', false);
    }

    public function test_sitemap_and_canonical_public_pages_are_available(): void
    {
        $this->get('/sitemap.xml')
            ->assertSuccessful()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<loc>', false)
            ->assertSee(url('/contact'), false);

        $this->get('/')
            ->assertSuccessful()
            ->assertSee('rel="canonical"', false);

        $this->get('/contact')->assertSuccessful();
        $this->get('/learn')->assertSuccessful();
        $this->get('/exams')->assertSuccessful();
    }

    public function test_admin_routes_require_administrator_authorization(): void
    {
        $this->get(route('admin.questions.dashboard'))->assertRedirect();

        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.questions.dashboard'))
            ->assertForbidden();

        $admin = User::factory()->administrator()->create();

        $this->actingAs($admin)
            ->get(route('admin.questions.dashboard'))
            ->assertSuccessful();
    }

    public function test_adsense_remains_disabled_by_default_configuration(): void
    {
        $this->assertFalse((bool) config('ads.enabled'));
        $this->assertTrue((bool) config('ads.require_consent'));
        $this->assertFalse((bool) config('ads.consent_granted'));

        $this->get('/')
            ->assertSuccessful()
            ->assertDontSee('googlesyndication', false)
            ->assertDontSee('adsbygoogle', false);
    }
}
