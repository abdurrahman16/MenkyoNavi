<?php

namespace App\Support\Content;

class RoadSignsGuide extends EducationalGuide
{
    public const LANG_KEY = 'road_signs';

    /**
     * @var list<string>
     */
    public const CATEGORY_KEYS = [
        'guide',
        'warning',
        'regulatory',
        'instruction',
        'supplementary',
    ];

    public static function langKey(): string
    {
        return self::LANG_KEY;
    }

    public static function configKey(): string
    {
        return 'road_signs';
    }

    protected static function aboutName(): string
    {
        return 'Japanese road signs learning library';
    }

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
            'library_heading',
            'categories_heading',
        ];
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

        if (! filled(data_get($content, 'categories.regulatory.title'))
            || ! filled(data_get($content, 'faq.0.q'))
            || ! filled(data_get($content, 'practice.items.0.q'))
            || ! filled(data_get($content, 'visuals.alts.hero'))) {
            return false;
        }

        $requiredSignFields = [
            'title',
            'meaning_short',
            'meaning',
            'must_do',
            'example',
            'misconception',
            'alt',
            'keywords',
        ];

        foreach (array_keys(self::signCatalog()) as $signId) {
            foreach ($requiredSignFields as $field) {
                $value = data_get($content, "signs.{$signId}.{$field}");

                if ($field === 'keywords') {
                    if (! is_array($value) || $value === []) {
                        return false;
                    }

                    continue;
                }

                if (! filled($value)) {
                    return false;
                }
            }
        }

        $practiceItems = data_get($content, 'practice.items', []);
        if (! is_array($practiceItems) || count($practiceItems) < 6) {
            return false;
        }

        foreach ($practiceItems as $item) {
            if (! filled(data_get($item, 'q'))
                || ! filled(data_get($item, 'explain'))
                || ! is_array(data_get($item, 'choices'))
                || count(data_get($item, 'choices', [])) !== 4
                || ! is_int(data_get($item, 'answer'))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Detect leftover English educational placeholders in non-English locales.
     */
    public static function hasEnglishPlaceholderBody(?string $locale = null): bool
    {
        $locale ??= app()->getLocale();

        if (in_array($locale, ['en', 'ja'], true)) {
            return false;
        }

        $path = lang_path($locale.'/'.static::langKey().'.php');
        $enPath = lang_path('en/'.static::langKey().'.php');

        if (! is_file($path) || ! is_file($enPath)) {
            return true;
        }

        /** @var array<string, mixed> $content */
        $content = require $path;
        /** @var array<string, mixed> $english */
        $english = require $enPath;

        $probeKeys = [
            'intro',
            'subtitle',
            'categories.regulatory.body',
            'signs.temporary-stop.meaning',
            'signs.no-parking.meaning',
            'practice.items.0.q',
            'faq.0.a',
            'visuals.captions.hero',
        ];

        foreach ($probeKeys as $key) {
            $value = data_get($content, $key);
            $englishValue = data_get($english, $key);

            if (is_string($value) && is_string($englishValue) && $value === $englishValue && strlen($englishValue) > 20) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function signCatalog(): array
    {
        /** @var array<string, array<string, mixed>> $signs */
        $signs = self::manifest()['signs'] ?? [];

        return $signs;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function enrichedSigns(?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $content = static::content($locale);
        $categories = self::manifest()['categories'] ?? [];

        $items = [];

        foreach (self::signCatalog() as $id => $sign) {
            $localized = data_get($content, "signs.{$id}", []);
            $categoryKey = (string) ($sign['category'] ?? '');

            $items[] = [
                'id' => $id,
                'official_name_ja' => (string) ($sign['official_name_ja'] ?? ''),
                'code' => (string) ($sign['code'] ?? ''),
                'official_variants' => array_values($sign['official_variants'] ?? []),
                'illustrated_variant' => (string) ($sign['illustrated_variant'] ?? ''),
                'illustration_variant_status' => (string) ($sign['illustration_variant_status'] ?? ''),
                'category' => $categoryKey,
                'category_official_ja' => (string) data_get($categories, "{$categoryKey}.official_ja", ''),
                'category_label' => (string) data_get($content, "categories.{$categoryKey}.title", $categoryKey),
                'illustration' => (string) ($sign['illustration'] ?? ''),
                'example_value' => $sign['example_value'] ?? null,
                'keywords' => array_values(array_unique(array_filter([
                    ...($sign['keywords'] ?? []),
                    ...($localized['keywords'] ?? []),
                    $sign['official_name_ja'] ?? null,
                    $localized['title'] ?? null,
                    $sign['code'] ?? null,
                    ...($sign['official_variants'] ?? []),
                ]))),
                'title' => (string) ($localized['title'] ?? $sign['official_name_ja'] ?? $id),
                'meaning_short' => (string) ($localized['meaning_short'] ?? ''),
                'meaning' => (string) ($localized['meaning'] ?? ''),
                'must_do' => (string) ($localized['must_do'] ?? ''),
                'example' => (string) ($localized['example'] ?? ''),
                'misconception' => (string) ($localized['misconception'] ?? ''),
                'related_topic' => (string) ($localized['related_topic'] ?? ''),
                'alt' => (string) ($localized['alt'] ?? $localized['title'] ?? ''),
                'review_state' => (string) ($sign['review_state'] ?? 'pending'),
                'classification_research' => is_array($sign['classification_research'] ?? null)
                    ? $sign['classification_research']
                    : [],
                'source_ids' => array_values($sign['source_ids'] ?? []),
                'pairs_with' => array_values($sign['pairs_with'] ?? []),
            ];
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function filterSigns(?string $category = null, ?string $query = null, ?string $locale = null): array
    {
        $category = filled($category) && $category !== 'all' ? (string) $category : null;
        $query = filled($query) ? mb_strtolower(trim((string) $query)) : null;

        return array_values(array_filter(
            static::enrichedSigns($locale),
            function (array $sign) use ($category, $query): bool {
                if ($category !== null && ($sign['category'] ?? null) !== $category) {
                    return false;
                }

                if ($query === null) {
                    return true;
                }

                $haystack = mb_strtolower(implode(' ', [
                    $sign['official_name_ja'] ?? '',
                    $sign['title'] ?? '',
                    $sign['meaning_short'] ?? '',
                    $sign['category_label'] ?? '',
                    $sign['code'] ?? '',
                    implode(' ', $sign['official_variants'] ?? []),
                    implode(' ', $sign['keywords'] ?? []),
                ]));

                return str_contains($haystack, $query);
            }
        ));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function practiceItems(?string $locale = null): array
    {
        $content = static::content($locale);
        $items = data_get($content, 'practice.items', []);

        return is_array($items) ? array_values($items) : [];
    }
}
