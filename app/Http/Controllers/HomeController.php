<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $locale = app()->getLocale();

        return view('home.index', [
            'jsonLd' => [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => config('menkyo.name'),
                'alternateName' => config('menkyo.name_ja'),
                'url' => url('/'),
                'description' => __('home.meta_description'),
                'inLanguage' => str_replace('_', '-', $locale),
            ],
        ]);
    }
}
