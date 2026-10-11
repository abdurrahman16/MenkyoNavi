<?php

namespace App\Notifications;

use App\Support\LocalePreference;
use App\Support\SupportedLocale;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;

class VerifyEmailNotification extends VerifyEmail
{
    /**
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $locale = $this->resolveLocale($notifiable);
        $verificationUrl = $this->verificationUrl($notifiable);
        $minutes = (int) Config::get('auth.verification.expire', 60);

        return (new MailMessage)
            ->locale($locale)
            ->subject(Lang::get('auth.verify_mail_subject', [], $locale))
            ->greeting(Lang::get('auth.verify_mail_greeting', ['name' => $notifiable->name ?? ''], $locale))
            ->line(Lang::get('auth.verify_mail_intro', [], $locale))
            ->action(Lang::get('auth.verify_mail_action', [], $locale), $verificationUrl)
            ->line(Lang::get('auth.verify_mail_expire', ['minutes' => $minutes], $locale))
            ->line(Lang::get('auth.verify_mail_ignore', [], $locale))
            ->salutation(Lang::get('auth.verify_mail_salutation', ['app' => config('app.name', 'MenkyoNavi')], $locale));
    }

    /**
     * @param  mixed  $notifiable
     */
    protected function resolveLocale($notifiable): string
    {
        $preferred = is_object($notifiable) ? ($notifiable->preferred_locale ?? null) : null;

        if (is_string($preferred) && SupportedLocale::isSupported($preferred)) {
            return $preferred;
        }

        $active = app()->getLocale();

        if (SupportedLocale::isSupported($active)) {
            return $active;
        }

        return app(LocalePreference::class)->defaultLocale();
    }
}
