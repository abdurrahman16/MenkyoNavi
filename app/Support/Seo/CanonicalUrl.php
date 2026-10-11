<?php

namespace App\Support\Seo;

class CanonicalUrl
{
    /**
     * Build a stable self-referencing canonical URL without tracking or filter query strings.
     */
    public static function current(): string
    {
        return self::from(url()->current());
    }

    /**
     * Normalize an absolute URL for use as a canonical / sitemap loc.
     */
    public static function from(string $url): string
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['host'])) {
            return $url;
        }

        if (isset($parts['scheme'])) {
            $scheme = $parts['scheme'];
        } elseif (app()->isProduction()) {
            $scheme = 'https';
        } else {
            $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'http';
        }
        $host = $parts['host'];
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = $parts['path'] ?? '/';

        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        return $scheme.'://'.$host.$port.$path;
    }

    /**
     * Absolute URL for a named route, stripped of query strings.
     */
    public static function route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        return self::from(route($name, $parameters, $absolute));
    }
}
