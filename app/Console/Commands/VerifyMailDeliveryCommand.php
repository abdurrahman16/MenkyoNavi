<?php

namespace App\Console\Commands;

use App\Mail\ContactMessageMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class VerifyMailDeliveryCommand extends Command
{
    protected $signature = 'menkyo:verify-mail-delivery
                            {--force : Allow in production without confirmation}';

    protected $description = 'Send a private test contact message to the configured recipient (never prints the full address publicly).';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force') && ! $this->confirm('Send a test contact email in production?')) {
            $this->warn('Aborted.');

            return self::FAILURE;
        }

        $recipient = (string) (config('menkyo.contact_recipient_email') ?: '');

        if ($recipient === '' || filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            $this->error('CONTACT_RECIPIENT_EMAIL is missing or invalid in environment configuration.');

            return self::FAILURE;
        }

        $mailer = (string) config('mail.default');
        $this->info('Mailer: '.$mailer);
        $this->info('Recipient configured: '.self::maskEmail($recipient));

        if (in_array($mailer, ['log', 'array'], true)) {
            $this->warn('Current mailer does not deliver to an external inbox. Configure SMTP/SES for production delivery.');
        }

        try {
            Mail::to($recipient)->send(new ContactMessageMail([
                'name' => 'MenkyoNavi Mail Check',
                'email' => (string) (config('mail.from.address') ?: 'noreply@localhost'),
                'subject' => 'Mail delivery verification',
                'message' => 'This is an administrator-triggered delivery check from the MenkyoNavi application. No visitor data is included.',
            ]));
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Mail send failed. Check mail configuration and application logs.');

            return self::FAILURE;
        }

        if ($mailer === 'log') {
            $this->warn('Message written via the log mailer only — check storage/logs. It was not emailed externally.');
        } else {
            $this->info('Message accepted by the configured mail transport.');
        }

        return self::SUCCESS;
    }

    private static function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        if ($local === '' || $domain === '') {
            return '[invalid]';
        }

        $visible = mb_substr($local, 0, 1);

        return $visible.'***@'.$domain;
    }
}
