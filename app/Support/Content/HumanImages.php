<?php

namespace App\Support\Content;

class HumanImages
{
    /**
     * @return array<string, mixed>|null
     */
    public static function get(string $key): ?array
    {
        $asset = config("content.human_images.assets.{$key}");

        return is_array($asset) ? $asset : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function all(): array
    {
        /** @var array<string, array<string, mixed>> $assets */
        $assets = config('content.human_images.assets', []);

        return array_values($assets);
    }

    public static function pathExists(string $key): bool
    {
        $asset = self::get($key);
        if ($asset === null) {
            return false;
        }

        $relative = (string) ($asset['path'] ?? '');

        return $relative !== '' && is_file(public_path($relative));
    }
}
