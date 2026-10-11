<?php

namespace App\Support\Seo;

class IndexingPolicy
{
    /**
     * Whether public HTML responses may advertise indexability.
     *
     * Defaults to production only. Override with MENKYO_ALLOW_INDEXING when
     * staging must stay noindex even if APP_ENV is mislabeled, or when tests
     * need production-like SEO assertions.
     */
    public static function allowsPublicIndexing(): bool
    {
        $override = config('menkyo.seo.allow_indexing');

        if ($override !== null && $override !== '') {
            return filter_var($override, FILTER_VALIDATE_BOOLEAN);
        }

        return app()->isProduction();
    }

    /**
     * Resolve the final robots directive for a page.
     *
     * Non-indexable environments always force noindex so local/staging copies
     * are not invited into Google's index. Stronger page-level nofollow is preserved.
     */
    public static function resolveRobots(?string $pageRobots): string
    {
        $normalized = is_string($pageRobots) ? strtolower(trim($pageRobots)) : '';

        if (! self::allowsPublicIndexing()) {
            if ($normalized !== '' && str_contains($normalized, 'nofollow')) {
                return 'noindex, nofollow';
            }

            return 'noindex, follow';
        }

        if ($normalized === '') {
            return 'index, follow';
        }

        return $pageRobots;
    }

    public static function googleSiteVerificationToken(): ?string
    {
        $token = config('menkyo.seo.google_site_verification');

        if (! is_string($token)) {
            return null;
        }

        $token = trim($token);

        return $token !== '' ? $token : null;
    }
}
