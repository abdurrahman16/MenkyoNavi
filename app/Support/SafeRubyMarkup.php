<?php

namespace App\Support;

use Illuminate\Support\Str;

class SafeRubyMarkup
{
    /**
     * Render structured reading segments as escaped HTML ruby markup.
     *
     * @param  list<array{text: string, reading?: string|null}>|null  $segments
     */
    public static function fromReadingData(?array $segments): string
    {
        if ($segments === null || $segments === []) {
            return '';
        }

        $html = '';

        foreach ($segments as $segment) {
            $text = e((string) ($segment['text'] ?? ''));
            $reading = isset($segment['reading']) ? trim((string) $segment['reading']) : '';

            if ($reading === '') {
                $html .= $text;

                continue;
            }

            $html .= '<ruby>'.$text.'<rt>'.e($reading).'</rt></ruby>';
        }

        return $html;
    }

    /**
     * Strip any HTML tags from free-form text before storage/display.
     */
    public static function plainText(string $value): string
    {
        return trim(Str::of(strip_tags($value))->toString());
    }
}
