<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

class Advertising
{
    /**
     * Live AdSense may load only when explicitly enabled with a real publisher ID
     * and (when required) advertising consent.
     */
    public static function isLiveReady(): bool
    {
        return (bool) config('ads.enabled')
            && filled(self::clientId())
            && (! (bool) config('ads.require_consent') || (bool) config('ads.consent_granted'));
    }

    public static function layoutPreviewActive(): bool
    {
        return (bool) config('ads.layout_preview')
            && ! app()->environment('production');
    }

    public static function shouldLoadAutoAdsScript(): bool
    {
        return self::isLiveReady()
            && (bool) config('ads.auto_enabled')
            && ! self::isExcludedFromAdvertising();
    }

    public static function shouldRenderManualSlots(): bool
    {
        return self::isLiveReady() && (bool) config('ads.manual_enabled');
    }

    /**
     * Normalized AdSense client ID (ca-pub-…).
     */
    public static function clientId(): ?string
    {
        $raw = trim((string) config('ads.publisher_id'));

        if ($raw === '') {
            return null;
        }

        if (preg_match('/^ca-pub-\d{10,20}$/', $raw) === 1) {
            return $raw;
        }

        if (preg_match('/^pub-\d{10,20}$/', $raw) === 1) {
            return 'ca-'.$raw;
        }

        return null;
    }

    /**
     * ads.txt publisher identifier (pub-… without ca- prefix).
     */
    public static function adsTxtPublisherId(): ?string
    {
        $client = self::clientId();

        if ($client === null) {
            return null;
        }

        return str_starts_with($client, 'ca-') ? substr($client, 3) : $client;
    }

    /**
     * Serve /ads.txt for publisher verification when a real publisher ID is set.
     * Independent of ADSENSE_ENABLED so verification can precede live ad display.
     */
    public static function shouldServeAdsTxt(): bool
    {
        return self::adsTxtPublisherId() !== null;
    }

    public static function isExcludedFromAdvertising(?string $routeName = null): bool
    {
        $routeName ??= Route::currentRouteName();

        if (! is_string($routeName) || $routeName === '') {
            return true;
        }

        foreach (config('ads.excluded_route_patterns', []) as $pattern) {
            if (self::routeMatches((string) $pattern, $routeName)) {
                return true;
            }
        }

        return false;
    }

    public static function slotUnitId(string $slotName): ?string
    {
        $unitId = config("ads.slots.{$slotName}.ad_unit_id");

        if (! is_string($unitId) || trim($unitId) === '') {
            return null;
        }

        $unitId = trim($unitId);

        return preg_match('/^\d{5,20}$/', $unitId) === 1 ? $unitId : null;
    }

    /**
     * Whether a named manual/preview slot should occupy layout space.
     */
    public static function shouldRenderSlot(string $slotName, ?string $context = null): bool
    {
        $slotConfig = config("ads.slots.{$slotName}");
        $allowedSlots = config('ads.allowed_slots', []);
        $forbiddenContexts = config('ads.forbidden_contexts', []);

        $slotKnown = is_array($slotConfig) || in_array($slotName, $allowedSlots, true);
        $slotEnabled = is_array($slotConfig) ? ($slotConfig['enabled'] ?? true) : in_array($slotName, $allowedSlots, true);
        $contextBlocked = filled($context) && in_array($context, $forbiddenContexts, true);

        if (! $slotKnown || $contextBlocked || self::isExcludedFromAdvertising()) {
            return false;
        }

        // Local layout preview may show labeled placeholders for known slots,
        // including exam regions that stay disabled for live AdSense.
        if (self::layoutPreviewActive()) {
            return true;
        }

        if (! $slotEnabled) {
            return false;
        }

        return self::shouldRenderManualSlots()
            && filled(self::slotUnitId($slotName))
            && filled(self::clientId());
    }

    private static function routeMatches(string $pattern, string $routeName): bool
    {
        if (! str_contains($pattern, '*')) {
            return $routeName === $pattern;
        }

        $regex = '/^'.str_replace('\*', '.*', preg_quote($pattern, '/')).'$/';

        return preg_match($regex, $routeName) === 1;
    }
}
