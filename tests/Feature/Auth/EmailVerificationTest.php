<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use App\Support\MailDelivery;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\Feature\Exams\CreatesPracticeExams;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use CreatesPracticeExams;
    use RefreshDatabase;

    public function test_email_verification_screen_explains_log_mailer_honestly(): void
    {
        Config::set('mail.default', 'log');
        $user = User::factory()->unverified()->create();

        $this->assertFalse(MailDelivery::usesExternalTransport());

        $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get('/verify-email')
            ->assertSuccessful()
            ->assertSee('data-email-verification-notice', false)
            ->assertSee('data-mail-log-notice', false)
            ->assertSee(__('auth.verify_title', [], 'en'), false)
            ->assertSee(__('auth.verify_log_mailer_notice', [], 'en'), false)
            ->assertDontSee('SMTP', false)
            ->assertDontSee(config('mail.from.address') ?? 'noop@example.test', false);
    }

    public function test_email_can_be_verified_with_valid_signed_url(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl)->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_expired_signature_is_rejected_and_keeps_account_unverified(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinutes(5),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->actingAs($user)
            ->get($verificationUrl)
            ->assertRedirect(route('verification.notice'));

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $this->assertSame('verification-link-invalid', session('status'));
    }

    public function test_verification_link_cannot_verify_another_users_account(): void
    {
        $owner = User::factory()->unverified()->create();
        $intruder = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $owner->id, 'hash' => sha1($owner->email)]
        );

        $this->actingAs($intruder)->get($verificationUrl)->assertForbidden();

        $this->assertFalse($owner->fresh()->hasVerifiedEmail());
        $this->assertFalse($intruder->fresh()->hasVerifiedEmail());
    }

    public function test_resend_uses_locale_aware_notification_and_log_status(): void
    {
        Config::set('mail.default', 'log');
        Notification::fake();

        $user = User::factory()->unverified()->create([
            'preferred_locale' => 'ja',
        ]);

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect()
            ->assertSessionHas('status', 'verification-link-logged');

        Notification::assertSentTo($user, VerifyEmailNotification::class);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_resend_is_throttled(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user);

        for ($i = 0; $i < 6; $i++) {
            $this->post(route('verification.send'))->assertRedirect();
        }

        $this->post(route('verification.send'))->assertStatus(429);
    }

    public function test_unverified_users_cannot_start_practice_or_open_dashboard(): void
    {
        $user = User::factory()->unverified()->create();
        $examType = $this->examTypeWithRules(1, 0, 1);
        $this->publishedTrueFalse($examType, true);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($user)
            ->post(route('exams.attempts.store', $examType), [
                'attempt_type' => 'demo',
            ])
            ->assertRedirect(route('verification.notice'));
    }

    public function test_verified_users_can_open_dashboard_and_see_onboarding_cta(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard', ['verified' => 1]))
            ->assertSuccessful()
            ->assertSee('data-email-verified-banner', false)
            ->assertSee('data-onboarding-demo-cta', false)
            ->assertSee(__('panel.verified_banner_title', [], 'en'), false)
            ->assertSee(route('exams.karimen', absolute: false), false);
    }

    public function test_guests_keep_access_to_public_learning_pages(): void
    {
        $this->get(route('learn.index'))->assertSuccessful();
        $this->get(route('learn.road-signs'))->assertSuccessful();
        $this->get(route('exams.karimen'))->assertSuccessful();
    }

    public function test_eight_locales_include_verification_ui_and_mail_copy(): void
    {
        foreach (['ja', 'en', 'zh', 'vi', 'fil', 'ne', 'bn', 'ko'] as $locale) {
            foreach ([
                'auth.verify_title',
                'auth.verify_resend',
                'auth.verify_log_mailer_notice',
                'auth.verify_mail_subject',
                'auth.verify_mail_action',
                'auth.register_title',
                'panel.verified_banner_title',
                'panel.start_demo_practice',
            ] as $key) {
                $value = __($key, [], $locale);
                $this->assertNotSame($key, $value, "Missing {$key} for {$locale}");
                $this->assertNotSame('', $value);
            }
        }
    }

    public function test_failed_mail_send_does_not_verify_account(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'fail-mail@example.com',
        ]);

        $throwingUser = new class extends User
        {
            public $id;

            public $email;

            public $email_verified_at = null;

            public function hasVerifiedEmail(): bool
            {
                return false;
            }

            public function sendEmailVerificationNotification(): void
            {
                throw new \RuntimeException('smtp unavailable');
            }
        };
        $throwingUser->id = $user->id;
        $throwingUser->email = $user->email;

        $request = Request::create(route('verification.send'), 'POST');
        $request->setLaravelSession($this->app['session.store']);
        $request->setUserResolver(fn () => $throwingUser);

        $response = app(EmailVerificationNotificationController::class)
            ->store($request);

        $this->assertTrue($response->isRedirect());
        $this->assertSame('verification-send-failed', $request->session()->get('status'));
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
