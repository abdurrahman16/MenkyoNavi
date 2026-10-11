<?php

namespace App\Support\Content;

use Illuminate\Support\Facades\Lang;

abstract class EducationalGuide
{
    abstract public static function langKey(): string;

    abstract public static function configKey(): string;

    abstract protected static function aboutName(): string;

    /**
     * @return list<string>
     */
    protected static function requiredContentKeys(): array
    {
        return [
            'meta_title',
            'meta_description',
            'title',
            'intro',
            'sources_heading',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function manifest(): array
    {
        return config('content.'.static::configKey(), []);
    }

    public static function localeStatus(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return (string) data_get(self::manifest(), "locales.{$locale}.status", 'pending');
    }

    public static function isIndexable(?string $locale = null): bool
    {
        $locale ??= app()->getLocale();

        return (bool) data_get(self::manifest(), "locales.{$locale}.indexable", false)
            && static::hasCompleteContent($locale);
    }

    public static function hasCompleteContent(?string $locale = null): bool
    {
        $locale ??= app()->getLocale();

        $path = lang_path($locale.'/'.static::langKey().'.php');
        if (! is_file($path)) {
            return false;
        }

        /** @var array<string, mixed> $content */
        $content = require $path;

        foreach (static::requiredContentKeys() as $key) {
            if (! filled(data_get($content, $key))) {
                return false;
            }
        }

        return filled(data_get($content, 'fundamentals.title'))
            || filled(data_get($content, 'meaning.title'));
    }

    /**
     * @return array<string, mixed>
     */
    public static function content(?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        if (! static::hasCompleteContent($locale)) {
            $fallback = (string) data_get(self::manifest(), 'authoritative_locale', 'ja');

            return Lang::get(static::langKey(), [], $fallback);
        }

        return Lang::get(static::langKey(), [], $locale);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function sources(): array
    {
        return array_values(self::manifest()['sources'] ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    public static function assets(): array
    {
        return self::manifest()['assets'] ?? self::manifest()['diagrams'] ?? [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function jsonLd(?string $locale = null): ?array
    {
        $locale ??= app()->getLocale();

        if (! static::isIndexable($locale)) {
            return null;
        }

        $content = static::content($locale);
        $manifest = self::manifest();
        $url = route((string) $manifest['route']);

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $content['title'] ?? '',
            'description' => $content['meta_description'] ?? '',
            'inLanguage' => str_replace('_', '-', $locale),
            'datePublished' => $manifest['published_at'] ?? null,
            'dateModified' => $manifest['updated_at'] ?? null,
            'mainEntityOfPage' => $url,
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => 'MenkyoNavi',
                'url' => url('/'),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'MenkyoNavi',
                'url' => url('/'),
            ],
            'about' => [
                '@type' => 'Thing',
                'name' => static::aboutName(),
            ],
        ];
    }

    public static function robots(?string $locale = null): ?string
    {
        return static::isIndexable($locale) ? null : 'noindex, follow';
    }
}
