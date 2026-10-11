<?php

namespace App\Support\Content;

class PedestrianPriorityGuide extends EducationalGuide
{
    public const LANG_KEY = 'pedestrian_priority';

    public static function langKey(): string
    {
        return self::LANG_KEY;
    }

    public static function configKey(): string
    {
        return 'pedestrian_priority';
    }

    protected static function aboutName(): string
    {
        return 'Pedestrian priority and crosswalk rules in Japan';
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
            'meaning.title',
            'meaning.p1',
            'meaning.p2',
            'principles.items.0.body',
            'observation.p1',
            'preparing.p1',
            'unsignalized.p1',
            'faq.0.q',
            'faq.0.a',
            'scenarios.items.0.ask',
            'scenarios.items.0.reveal',
            'practice.items.0.q',
            'practice.items.0.explain',
            'visuals.alts.approach',
            'visuals.captions.approach',
            'visuals.alts.preparing',
            'visuals.captions.preparing',
        ];

        foreach ($requiredFilled as $key) {
            if (! filled(data_get($content, $key))) {
                return false;
            }
        }

        $practiceItems = data_get($content, 'practice.items', []);
        if (! is_array($practiceItems) || count($practiceItems) < 3) {
            return false;
        }

        foreach ($practiceItems as $item) {
            if (! filled(data_get($item, 'q'))
                || ! filled(data_get($item, 'explain'))
                || ! is_array(data_get($item, 'choices'))
                || count(data_get($item, 'choices', [])) !== 3
                || ! is_int(data_get($item, 'answer'))) {
                return false;
            }
        }

        $scenarioItems = data_get($content, 'scenarios.items', []);
        if (! is_array($scenarioItems) || count($scenarioItems) < 3) {
            return false;
        }

        foreach ($scenarioItems as $item) {
            if (! filled(data_get($item, 'ask')) || ! filled(data_get($item, 'reveal'))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Detect leftover English educational placeholders in non-English locales.
     *
     * Compares long probe strings to the English draft. Official Japanese
     * terms, organization names, and short shared vocabulary are not probed.
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
            'meaning.p1',
            'meaning.p2',
            'principles.items.0.body',
            'preparing.p1',
            'scenarios.items.0.reveal',
            'practice.items.0.explain',
            'faq.0.a',
            'visuals.captions.approach',
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
     * @return list<int>
     */
    public static function practiceAnswerMap(?string $locale = null): array
    {
        $content = static::content($locale);
        $items = data_get($content, 'practice.items', []);

        if (! is_array($items)) {
            return [];
        }

        return array_map(
            static fn (array $item): int => (int) ($item['answer'] ?? -1),
            array_values($items)
        );
    }
}
