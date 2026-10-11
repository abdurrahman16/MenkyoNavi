<?php

namespace App\Http\Controllers;

use App\Support\Seo\IndexingPolicy;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $sitemapUrl = url('/sitemap.xml');

        if (IndexingPolicy::allowsPublicIndexing()) {
            $lines = [
                'User-agent: *',
                'Allow: /',
                'Disallow: /dashboard',
                'Disallow: /profile',
                'Disallow: /admin',
                'Disallow: /login',
                'Disallow: /register',
                'Disallow: /forgot-password',
                'Disallow: /reset-password',
                'Disallow: /verify-email',
                'Disallow: /confirm-password',
                'Disallow: /mistakes',
                'Disallow: /exams/attempts',
                '',
                'Sitemap: '.$sitemapUrl,
                '',
            ];
        } else {
            // Local/staging: discourage crawling of development copies.
            // Page-level noindex remains the authoritative indexing control.
            $lines = [
                'User-agent: *',
                'Disallow: /',
                '',
                'Sitemap: '.$sitemapUrl,
                '',
            ];
        }

        return response(implode("\n", $lines), 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
