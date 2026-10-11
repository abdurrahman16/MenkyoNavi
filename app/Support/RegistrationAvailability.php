<?php

namespace App\Support;

class RegistrationAvailability
{
    /**
     * Whether public self-registration is currently allowed.
     *
     * Explicit REGISTRATION_ENABLED wins. Otherwise local/testing stay open for
     * development, and other environments require an external mail transport so
     * verification messages can actually be delivered.
     */
    public static function allowsPublicRegistration(): bool
    {
        $configured = config('menkyo.registration_enabled');

        if ($configured !== null && $configured !== '') {
            return filter_var($configured, FILTER_VALIDATE_BOOLEAN);
        }

        if (app()->environment('local', 'testing')) {
            return true;
        }

        return MailDelivery::usesExternalTransport();
    }
}
