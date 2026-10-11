<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{name: string, email: string, subject: string, message: string}  $payload
     */
    public function __construct(public array $payload) {}

    public function envelope(): Envelope
    {
        $subject = str_replace(["\r", "\n"], '', $this->payload['subject']);
        $replyTo = str_replace(["\r", "\n"], '', $this->payload['email']);
        $fromAddress = (string) config('mail.from.address');
        $fromName = (string) config('mail.from.name');

        return new Envelope(
            from: filter_var($fromAddress, FILTER_VALIDATE_EMAIL)
                ? new Address($fromAddress, $fromName !== '' ? $fromName : (string) config('app.name'))
                : null,
            subject: '[MenkyoNavi Contact] '.$subject,
            replyTo: [
                new Address($replyTo, str_replace(["\r", "\n"], '', $this->payload['name'])),
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contact-message',
            with: [
                'name' => $this->payload['name'],
                'email' => $this->payload['email'],
                'subjectLine' => $this->payload['subject'],
                'body' => $this->payload['message'],
                'submittedAt' => now()->timezone(config('app.timezone'))->toDateTimeString(),
            ],
        );
    }
}
