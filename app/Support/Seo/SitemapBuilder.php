<?php

namespace App\Support\Seo;

use App\Support\Content\IntersectionPriorityGuide;
use App\Support\Content\PedestrianPriorityGuide;
use App\Support\Content\RoadSignsGuide;
use App\Support\Content\StudyMethodsGuide;
use Illuminate\Support\Facades\Route;

class SitemapBuilder
{
    /**
     * Route names that must never appear in the sitemap (noindex or private).
     *
     * @var list<string>
     */
    public const EXCLUDED_ROUTES = [
        'privacy-policy',
        'terms-of-service',
    ];

    /**
     * Content manifests that supply a reliable lastmod for a route.
     *
     * @var array<string, class-string>
     */
    private const GUIDE_LASTMOD = [
        'learn.road-signs' => RoadSignsGuide::class,
        'learn.traffic-rules.pedestrian-priority' => PedestrianPriorityGuide::class,
        'learn.traffic-rules.intersection-priority' => IntersectionPriorityGuide::class,
        'learn.study-methods' => StudyMethodsGuide::class,
    ];

    /**
     * @return list<array{route: string, loc: string, lastmod: ?string}>
     */
    public function entries(): array
    {
        $entries = [];
        $seen = [];

        foreach ($this->candidateRouteNames() as $name) {
            if (! Route::has($name) || in_array($name, self::EXCLUDED_ROUTES, true)) {
                continue;
            }

            $loc = CanonicalUrl::route($name);

            if (isset($seen[$loc])) {
                continue;
            }

            $seen[$loc] = true;

            $entries[] = [
                'route' => $name,
                'loc' => $loc,
                'lastmod' => $this->lastmodFor($name),
            ];
        }

        usort($entries, fn (array $a, array $b): int => strcmp($a['loc'], $b['loc']));

        return $entries;
    }

    /**
     * @return list<string>
     */
    public function locs(): array
    {
        return array_values(array_map(
            fn (array $entry): string => $entry['loc'],
            $this->entries(),
        ));
    }

    /**
     * @return list<string>
     */
    public function candidateRouteNames(): array
    {
        /** @var list<string> $routes */
        $routes = array_values(array_unique(config('menkyo.sitemap_routes', [])));

        return $routes;
    }

    /**
     * @return list<string>
     */
    public function excludedRouteNames(): array
    {
        return self::EXCLUDED_ROUTES;
    }

    private function lastmodFor(string $routeName): ?string
    {
        $guideClass = self::GUIDE_LASTMOD[$routeName] ?? null;

        if ($guideClass === null) {
            return null;
        }

        $updatedAt = data_get($guideClass::manifest(), 'updated_at');

        if (! is_string($updatedAt) || trim($updatedAt) === '') {
            return null;
        }

        // Accept W3C date (YYYY-MM-DD) only — never invent timestamps.
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $updatedAt)) {
            return null;
        }

        return $updatedAt;
    }
}
