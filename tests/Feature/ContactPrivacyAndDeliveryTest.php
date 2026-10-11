<?php

namespace Tests\Feature;

use App\Mail\ContactMessageMail;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ContactPrivacyAndDeliveryTest extends TestCase
{
    private const RECIPIENT = 'owner@menkyo-navi.test';

    /**
     * @return array<string, array{0: string}>
     */
    public static function publicPagesThatMustHideRecipient(): array
    {
        return [
            'home' => ['/'],
            'contact' => ['/contact'],
            'about' => ['/about'],
            'privacy' => ['/privacy-policy'],
            'terms' => ['/terms-of-service'],
            'cookies' => ['/cookie-policy'],
            'advertising' => ['/advertising-disclosure'],
        ];
    }

    #[DataProvider('publicPagesThatMustHideRecipient')]
    public function test_recipient_email_is_absent_from_public_html(string $uri): void
    {
        $this->assertSame(self::RECIPIENT, config('menkyo.contact_recipient_email'));

        $this->get($uri)
            ->assertSuccessful()
            ->assertDontSee(self::RECIPIENT, false)
            ->assertDontSee('mailto:'.self::RECIPIENT, false);
    }

    public function test_footer_links_to_contact_instead_of_mailto_recipient(): void
    {
        $html = $this->get('/')->assertSuccessful()->getContent();

        $this->assertStringNotContainsString(self::RECIPIENT, $html);
        $this->assertStringContainsString('/contact', $html);
        $this->assertStringNotContainsString('mailto:'.self::RECIPIENT, $html);
    }

    public function test_homepage_structured_data_does_not_expose_recipient(): void
    {
        $html = $this->get('/')->assertSuccessful()->getContent();

        $this->assertStringContainsString('application/ld+json', $html);
        $this->assertStringNotContainsString(self::RECIPIENT, $html);
        $this->assertStringNotContainsString('"email"', $html);
    }

    public function test_contact_form_sends_to_configured_server_side_recipient(): void
    {
        Mail::fake();

        $this->from('/contact')
            ->post('/contact', [
                'name' => 'Alex Example',
                'email' => 'alex@example.com',
                'subject' => 'Question about study guides',
                'message' => 'I would like to know when mock exams will be available on MenkyoNavi.',
                'website' => '',
            ])
            ->assertRedirect(route('contact'))
            ->assertSessionHas('status');

        Mail::assertSent(ContactMessageMail::class, function (ContactMessageMail $mail): bool {
            return $mail->hasTo(self::RECIPIENT)
                && $mail->hasReplyTo('alex@example.com')
                && str_contains($mail->envelope()->subject, '[MenkyoNavi Contact]');
        });
    }

    public function test_contact_form_validation_and_honeypot_protection(): void
    {
        Mail::fake();

        $this->from('/contact')
            ->post('/contact', [])
            ->assertSessionHasErrors(['name', 'email', 'subject', 'message']);

        $this->from('/contact')
            ->post('/contact', [
                'name' => 'Bot',
                'email' => 'bot@example.com',
                'subject' => 'Spam',
                'message' => 'This is long enough to pass the minimum length check.',
                'website' => 'https://spam.example',
            ])
            ->assertSessionHasErrors(['website']);

        Mail::assertNothingSent();
    }

    public function test_log_mailer_reports_truthful_local_only_status(): void
    {
        Mail::fake();
        config(['mail.default' => 'log']);

        $this->from('/contact')
            ->post('/contact', [
                'name' => 'Alex Example',
                'email' => 'alex@example.com',
                'subject' => 'Local log check',
                'message' => 'Please confirm the log driver messaging stays truthful for developers.',
            ])
            ->assertRedirect(route('contact'))
            ->assertSessionHas('status', __('pages.contact.success_logged'));
    }

    public function test_mail_failures_are_reported_without_success_flash(): void
    {
        config(['menkyo.contact_recipient_email' => null]);

        $this->from('/contact')
            ->post('/contact', [
                'name' => 'Alex Example',
                'email' => 'alex@example.com',
                'subject' => 'Delivery failure check',
                'message' => 'This submission should surface a safe failure message to the visitor.',
            ])
            ->assertRedirect('/contact')
            ->assertSessionHas('error', __('pages.contact.failure'))
            ->assertSessionMissing('status');
    }

    public function test_japanese_contact_page_localization(): void
    {
        $this->get('/contact')
            ->assertSuccessful()
            ->assertSee('お問い合わせ', false)
            ->assertSee(__('pages.contact.instructions', [], 'ja'), false)
            ->assertDontSee(self::RECIPIENT, false);
    }

    public function test_contact_form_is_rate_limited(): void
    {
        Mail::fake();

        $payload = [
            'name' => 'Alex Example',
            'email' => 'alex@example.com',
            'subject' => 'Rate limit check',
            'message' => 'This message is long enough to satisfy the minimum contact form length.',
            'website' => '',
        ];

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from('/contact')
                ->post('/contact', $payload)
                ->assertRedirect(route('contact'));
        }

        $this->from('/contact')
            ->post('/contact', $payload)
            ->assertStatus(429);
    }

    public function test_contact_mailable_uses_configured_from_and_visitor_reply_to(): void
    {
        config([
            'mail.from.address' => 'noreply@menkyo-navi.test',
            'mail.from.name' => 'MenkyoNavi',
        ]);

        $mailable = new ContactMessageMail([
            'name' => 'Alex Example',
            'email' => 'alex@example.com',
            'subject' => 'From address check',
            'message' => 'Confirm From and Reply-To headers for contact delivery.',
        ]);

        $this->assertTrue($mailable->hasFrom('noreply@menkyo-navi.test', 'MenkyoNavi'));
        $this->assertTrue($mailable->hasReplyTo('alex@example.com', 'Alex Example'));
    }
}
