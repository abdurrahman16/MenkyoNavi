<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->withSession(['locale' => 'ja'])
            ->get('/register')
            ->assertSuccessful()
            ->assertSee(__('auth.register_title', [], 'ja'), false)
            ->assertSee('data-registration-form', false);
    }

    public function test_new_users_register_unverified_with_locale_and_redirect_to_verification_notice(): void
    {
        Notification::fake();
        Event::fake([Registered::class]);

        $response = $this->withSession(['locale' => 'vi'])
            ->post('/register', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice', absolute: false));

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->assertNull($user->email_verified_at);
        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertSame('vi', $user->preferred_locale);
        $this->assertFalse((bool) $user->is_admin);

        Event::assertDispatched(Registered::class);
    }

    public function test_registration_dispatches_verification_notification_and_rejects_duplicate_email(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'First User',
            'email' => 'dup@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('verification.notice', absolute: false));

        $user = User::query()->where('email', 'dup@example.com')->firstOrFail();
        Notification::assertSentTo($user, VerifyEmailNotification::class);

        $this->post('/logout');

        $this->post('/register', [
            'name' => 'Second User',
            'email' => 'dup@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('email');
    }

    public function test_registered_user_can_log_in_after_verification_with_same_password(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Login Ready',
            'email' => 'login-ready@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::query()->where('email', 'login-ready@example.com')->firstOrFail();
        $user->forceFill(['email_verified_at' => now()])->save();

        $this->post('/logout');

        $this->post('/login', [
            'email' => 'login-ready@example.com',
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user->fresh());
    }
}
