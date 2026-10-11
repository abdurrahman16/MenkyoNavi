<?php

namespace App\Support\Content;

class StudyMethodsGuide extends EducationalGuide
{
    public const LANG_KEY = 'study_methods';

    public static function langKey(): string
    {
        return self::LANG_KEY;
    }

    public static function configKey(): string
    {
        return 'study_methods';
    }

    protected static function aboutName(): string
    {
        return 'How to study effectively for Japanese driving license exams';
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
            'sections',
            'plans',
            'tips',
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

        return is_array(data_get($content, 'sections'))
            && count((array) data_get($content, 'sections')) >= 8
            && is_array(data_get($content, 'plans.seven.days'))
            && is_array(data_get($content, 'plans.fourteen.days'))
            && is_array(data_get($content, 'tips.items'))
            && count((array) data_get($content, 'tips.items')) >= 6;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function humanImage(string $role): ?array
    {
        $key = (string) data_get(self::manifest(), "images.{$role}");
        if ($key === '') {
            return null;
        }

        $asset = config("content.human_images.assets.{$key}");

        return is_array($asset) ? $asset : null;
    }
}
