<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Cookie;

class LocalePreference
{
    public const COOKIE_NAME = 'menkyo_locale';

    public const COOKIE_MINUTES = 60 * 24 * 365;

    public function defaultLocale(): string
    {
        return (string) config('menkyo.default_locale', 'ja');
    }

    public function normalize(string $locale): ?string
    {
        $aliases = config('menkyo.locale_aliases', []);
        $locale = $aliases[$locale] ?? $locale;

        if (! SupportedLocale::isSupported($locale)) {
            return null;
        }

        return $locale;
    }

    public function resolve(Request $request): string
    {
        $candidates = [
            $request->session()->get('locale'),
            $request->user()?->preferred_locale,
            $request->cookie(self::COOKIE_NAME),
        ];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || $candidate === '') {
                continue;
            }

            $normalized = $this->normalize($candidate);

            if ($normalized !== null) {
                return $normalized;
            }
        }

        return $this->defaultLocale();
    }

    public function activate(string $locale): string
    {
        $locale = $this->normalize($locale) ?? $this->defaultLocale();

        session(['locale' => $locale]);
        App::setLocale($locale);

        return $locale;
    }

    public function persist(Request $request, string $locale): Cookie
    {
        $locale = $this->normalize($locale) ?? $this->defaultLocale();

        $this->activate($locale);

        $user = $request->user();

        if ($user instanceof User && $user->preferred_locale !== $locale) {
            $user->forceFill(['preferred_locale' => $locale])->save();
        }

        return cookie(
            self::COOKIE_NAME,
            $locale,
            self::COOKIE_MINUTES,
            '/',
            null,
            null,
            true,
            false,
            'lax',
        );
    }
}
