<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\RegistrationAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_is_open_in_testing_by_default(): void
    {
        config(['menkyo.registration_enabled' => null]);

        $this->assertTrue(RegistrationAvailability::allowsPublicRegistration());

        $this->get('/register')
            ->assertSuccessful()
            ->assertSee('data-registration-form', false)
            ->assertDontSee('data-registration-closed', false);
    }

    public function test_registration_can_be_forced_closed(): void
    {
        config(['menkyo.registration_enabled' => false]);

        $this->assertFalse(RegistrationAvailability::allowsPublicRegistration());

        $this->get('/register')
            ->assertSuccessful()
            ->assertSee('data-registration-closed', false);

        $this->post('/register', [
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('register', absolute: false));

        $this->assertGuest();
        $this->assertNull(User::query()->where('email', 'blocked@example.com')->first());
    }

    public function test_production_without_external_mail_closes_registration(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config([
            'menkyo.registration_enabled' => null,
            'mail.default' => 'log',
        ]);

        $this->assertFalse(RegistrationAvailability::allowsPublicRegistration());
    }

    public function test_production_with_external_mail_allows_registration(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config([
            'menkyo.registration_enabled' => null,
            'mail.default' => 'smtp',
        ]);

        $this->assertTrue(RegistrationAvailability::allowsPublicRegistration());
    }
}
