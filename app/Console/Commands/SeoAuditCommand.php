<?php

namespace App\Console\Commands;

use App\Support\Content\IntersectionPriorityGuide;
use App\Support\Content\PedestrianPriorityGuide;
use App\Support\Content\RoadSignsGuide;
use App\Support\Seo\CanonicalUrl;
use App\Support\Seo\IndexingPolicy;
use App\Support\Seo\SitemapBuilder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

#[Signature('menkyo:seo-audit {--json : Output machine-readable JSON}')]
#[Description('Read-only technical SEO audit for robots, sitemap, meta, and indexing policy.')]
class SeoAuditCommand extends Command
{
    /**
     * @var list<class-string>
     */
    private const GUIDES = [
        RoadSignsGuide::class,
        PedestrianPriorityGuide::class,
        IntersectionPriorityGuide::class,
    ];

    public function handle(SitemapBuilder $sitemap): int
    {
        $report = $this->buildReport($sitemap);

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->renderReport($report);

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReport(SitemapBuilder $sitemap): array
    {
        $appUrl = (string) config('app.url');
        $entries = $sitemap->entries();
        $host = parse_url($appUrl, PHP_URL_HOST);
        $findings = [];

        if ($host === null || $host === '' || str_contains((string) $host, 'localhost') || str_contains((string) $host, '127.0.0.1')) {
            $findings[] = [
                'severity' => 'warning',
                'code' => 'missing_production_domain',
                'message' => 'APP_URL still points at a local host. Set the real HTTPS production origin before Search Console submission.',
            ];
        }

        if (File::exists(public_path('robots.txt'))) {
            $findings[] = [
                'severity' => 'error',
                'code' => 'static_robots_conflict',
                'message' => 'public/robots.txt exists and may bypass RobotsController.',
            ];
        }

        $brokenRoutes = [];
        foreach ($sitemap->candidateRouteNames() as $name) {
            if (! Route::has($name)) {
                $brokenRoutes[] = $name;
            }
        }

        if ($brokenRoutes !== []) {
            $findings[] = [
                'severity' => 'error',
                'code' => 'broken_sitemap_routes',
                'message' => 'Configured sitemap routes are missing: '.implode(', ', $brokenRoutes),
            ];
        }

        $locs = array_column($entries, 'loc');
        if (count($locs) !== count(array_unique($locs))) {
            $findings[] = [
                'severity' => 'error',
                'code' => 'duplicate_sitemap_locs',
                'message' => 'Sitemap contains duplicate loc values.',
            ];
        }

        $unreviewedIndexable = [];
        foreach (self::GUIDES as $guide) {
            foreach (array_keys(config('menkyo.locales', [])) as $locale) {
                $status = (string) data_get($guide::manifest(), "locales.{$locale}.status", '');
                $indexable = $guide::isIndexable($locale);
                if ($indexable && $status !== 'authored' && $locale !== 'ja') {
                    $unreviewedIndexable[] = $guide::langKey().':'.$locale;
                }
            }
        }

        if ($unreviewedIndexable !== []) {
            $findings[] = [
                'severity' => 'error',
                'code' => 'unreviewed_indexable_translations',
                'message' => 'Draft/unreviewed locales marked indexable: '.implode(', ', $unreviewedIndexable),
            ];
        } else {
            $findings[] = [
                'severity' => 'info',
                'code' => 'draft_translations_noindex',
                'message' => 'Non-Japanese educational guide drafts remain noindex.',
            ];
        }

        $findings[] = [
            'severity' => 'info',
            'code' => 'language_seo_limitation',
            'message' => 'Locales use session/cookie preference on shared paths. Do not emit hreflang or per-locale sitemap URLs until crawlable locale-prefixed routes exist.',
        ];

        if (IndexingPolicy::googleSiteVerificationToken() === null) {
            $findings[] = [
                'severity' => 'info',
                'code' => 'search_console_token_unset',
                'message' => 'GOOGLE_SITE_VERIFICATION is empty (expected until real Search Console ownership setup).',
            ];
        }

        $missingAssets = $this->missingGuideAssets();
        if ($missingAssets !== []) {
            $findings[] = [
                'severity' => 'warning',
                'code' => 'missing_assets',
                'message' => 'Missing public asset files: '.implode(', ', array_slice($missingAssets, 0, 8)),
            ];
        }

        return [
            'generated_at' => now()->toIso8601String(),
            'read_only' => true,
            'app_url' => $appUrl,
            'environment' => app()->environment(),
            'allows_public_indexing' => IndexingPolicy::allowsPublicIndexing(),
            'google_site_verification_configured' => IndexingPolicy::googleSiteVerificationToken() !== null,
            'static_robots_txt_present' => File::exists(public_path('robots.txt')),
            'sitemap' => [
                'url' => CanonicalUrl::from(url('/sitemap.xml')),
                'entry_count' => count($entries),
                'entries' => $entries,
                'excluded_routes' => $sitemap->excludedRouteNames(),
                'private_path_examples' => [
                    '/dashboard',
                    '/login',
                    '/register',
                    '/admin',
                    '/exams/attempts',
                    '/mistakes',
                ],
            ],
            'guides' => $this->guideIndexability(),
            'findings' => $findings,
            'notes' => [
                'Robots.txt controls crawling hints only — it is not access control.',
                'Sitemap inclusion must agree with page robots; noindex pages are excluded.',
                'This command does not crawl Google or claim indexing success.',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function guideIndexability(): array
    {
        $rows = [];

        foreach (self::GUIDES as $guide) {
            $locales = [];
            foreach (array_keys(config('menkyo.locales', [])) as $locale) {
                $locales[$locale] = [
                    'indexable' => $guide::isIndexable($locale),
                    'robots' => $guide::robots($locale) ?? 'index, follow',
                    'status' => $guide::localeStatus($locale),
                ];
            }

            $rows[] = [
                'guide' => $guide::langKey(),
                'route' => data_get($guide::manifest(), 'route'),
                'path' => data_get($guide::manifest(), 'path'),
                'locales' => $locales,
            ];
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    private function missingGuideAssets(): array
    {
        $missing = [];

        foreach (self::GUIDES as $guide) {
            foreach ($guide::assets() as $asset) {
                $path = is_array($asset) ? ($asset['path'] ?? null) : $asset;
                if (! is_string($path) || $path === '') {
                    continue;
                }

                if (! File::exists(public_path($path))) {
                    $missing[] = $path;
                }
            }
        }

        return array_values(array_unique($missing));
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function renderReport(array $report): void
    {
        $this->info('MenkyoNavi technical SEO audit (read-only)');
        $this->line('APP_URL: '.$report['app_url']);
        $this->line('Environment: '.$report['environment']);
        $this->line('Public indexing allowed: '.($report['allows_public_indexing'] ? 'yes' : 'no'));
        $this->line('Search Console token configured: '.($report['google_site_verification_configured'] ? 'yes' : 'no'));
        $this->line('Static public/robots.txt: '.($report['static_robots_txt_present'] ? 'PRESENT (conflict)' : 'absent (OK)'));
        $this->newLine();

        $this->info('Sitemap ('.$report['sitemap']['entry_count'].' URLs)');
        $this->line('Endpoint: '.$report['sitemap']['url']);
        foreach ($report['sitemap']['entries'] as $entry) {
            $lastmod = $entry['lastmod'] ? ' lastmod='.$entry['lastmod'] : '';
            $this->line('  - '.$entry['route'].' → '.$entry['loc'].$lastmod);
        }

        $this->newLine();
        $this->info('Excluded from sitemap');
        foreach ($report['sitemap']['excluded_routes'] as $route) {
            $this->line('  - '.$route.' (noindex public draft)');
        }
        foreach ($report['sitemap']['private_path_examples'] as $path) {
            $this->line('  - '.$path);
        }

        $this->newLine();
        $this->info('Educational guide indexability');
        foreach ($report['guides'] as $guide) {
            $this->line('  '.$guide['guide'].' ('.$guide['path'].')');
            foreach ($guide['locales'] as $locale => $meta) {
                $flag = $meta['indexable'] ? 'indexable' : 'noindex';
                $this->line('    '.$locale.': '.$flag.' ['.$meta['status'].']');
            }
        }

        $this->newLine();
        $this->info('Findings');
        foreach ($report['findings'] as $finding) {
            $this->line('  ['.$finding['severity'].'] '.$finding['code'].': '.$finding['message']);
        }
    }
}
