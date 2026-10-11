<?php

namespace App\Support\Content;

class IntersectionPriorityGuide extends EducationalGuide
{
    public const LANG_KEY = 'intersection_priority';

    public static function langKey(): string
    {
        return self::LANG_KEY;
    }

    public static function configKey(): string
    {
        return 'intersection_priority';
    }

    protected static function aboutName(): string
    {
        return 'Intersection priority and stop signs in Japan';
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
            'toc_label',
            'faq_heading',
        ];
    }

    public static function hasCompleteContent(?string $locale = null): bool
    {
        $locale ??= app()->getLocale();

        if (! parent::hasCompleteContent($locale)) {
            return false;
        }

        $path = lang_path($locale.'/'.static::langKey().'.php');
        /** @var array<string, mixed> $content */
        $content = require $path;

        if ($locale !== 'ja' && ! filled(data_get($content, 'draft_notice'))) {
            return false;
        }

        $requiredFilled = [
            'fundamentals.title',
            'fundamentals.p1',
            'fundamentals.p2',
            'stop_signs.title',
            'stop_signs.p1',
            'signals.title',
            'signals.p1',
            'turning.title',
            'turning.left_body',
            'turning.right_body',
            'uncontrolled.title',
            'uncontrolled.p1',
            'faq.0.q',
            'faq.0.a',
            'scenarios.items.0.ask',
            'scenarios.items.0.reveal',
            'visuals.alts.hero',
            'visuals.captions.hero',
            'visuals.alts.stop_sign',
            'visuals.captions.stop_sign',
            'interaction_left',
            'interaction_right',
            'interaction_hazard_show',
        ];

        foreach ($requiredFilled as $key) {
            if (! filled(data_get($content, $key))) {
                return false;
            }
        }

        $scenarioItems = data_get($content, 'scenarios.items', []);
        if (! is_array($scenarioItems) || count($scenarioItems) < 5) {
            return false;
        }

        foreach ($scenarioItems as $item) {
            if (! filled(data_get($item, 'ask')) || ! filled(data_get($item, 'reveal'))) {
                return false;
            }
        }

        $faq = data_get($content, 'faq', []);
        if (! is_array($faq) || count($faq) < 5) {
            return false;
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
            'fundamentals.p1',
            'fundamentals.p2',
            'stop_signs.p1',
            'signals.p1',
            'turning.left_body',
            'turning.right_body',
            'scenarios.items.0.reveal',
            'faq.0.a',
            'visuals.captions.hero',
            'related.karimen.title',
            'related.signs.title',
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
     * @return list<string>
     */
    public static function scenarioIds(?string $locale = null): array
    {
        $content = static::content($locale);
        $items = data_get($content, 'scenarios.items', []);

        if (! is_array($items)) {
            return [];
        }

        return array_values(array_map(
            static fn (array $item): string => (string) ($item['id'] ?? ''),
            $items
        ));
    }
}
