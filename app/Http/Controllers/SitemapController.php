<?php

namespace App\Http\Controllers;

use App\Support\Seo\SitemapBuilder;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(SitemapBuilder $sitemap): Response
    {
        $xml = view('seo.sitemap', [
            'urls' => $sitemap->entries(),
        ])->render();

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
