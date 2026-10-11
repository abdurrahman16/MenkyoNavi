<?php

namespace App\Support;

use InvalidArgumentException;

class SupportedLocale
{
    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(config('menkyo.locales', []));
    }

    public static function isSupported(string $locale): bool
    {
        $aliases = config('menkyo.locale_aliases', []);
        $locale = $aliases[$locale] ?? $locale;

        return in_array($locale, self::codes(), true);
    }

    public static function normalize(string $locale): string
    {
        $aliases = config('menkyo.locale_aliases', []);
        $locale = $aliases[$locale] ?? $locale;

        if (! self::isSupported($locale)) {
            throw new InvalidArgumentException("Unsupported locale [{$locale}].");
        }

        return $locale;
    }
}
