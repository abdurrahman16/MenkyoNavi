<?php

namespace App\Http\Controllers;

use App\Support\LocalePreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function switch(Request $request, string $locale, LocalePreference $locales): RedirectResponse
    {
        $normalized = $locales->normalize($locale);

        if ($normalized === null) {
            abort(404);
        }

        $cookie = $locales->persist($request, $normalized);

        return back()->withCookie($cookie);
    }
}
