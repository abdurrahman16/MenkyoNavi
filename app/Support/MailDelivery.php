<?php

namespace App\Support;

class MailDelivery
{
    /**
     * Whether the default mailer is expected to deliver to an external inbox.
     */
    public static function usesExternalTransport(?string $mailer = null): bool
    {
        $mailer ??= (string) config('mail.default');

        return ! in_array($mailer, ['log', 'array'], true);
    }

    public static function defaultMailer(): string
    {
        return (string) config('mail.default');
    }
}
