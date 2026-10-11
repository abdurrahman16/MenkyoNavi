<?php

namespace Tests\Feature;

use App\Mail\ContactMessageMail;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    public function test_contact_form_requires_valid_fields(): void
    {
        $this->from('/contact')
            ->post('/contact', [])
            ->assertSessionHasErrors(['name', 'email', 'subject', 'message']);
    }

    public function test_contact_form_sends_mail_without_persisting_submission(): void
    {
        Mail::fake();

        $this->from('/contact')
            ->post('/contact', [
                'name' => 'Alex Example',
                'email' => 'alex@example.com',
                'subject' => 'Question about study guides',
                'message' => 'I would like to know when mock exams will be available on MenkyoNavi.',
            ])
            ->assertRedirect(route('contact'))
            ->assertSessionHas('status');

        Mail::assertSent(ContactMessageMail::class, function (ContactMessageMail $mail): bool {
            return $mail->hasTo((string) config('menkyo.contact_recipient_email'));
        });
    }
}
