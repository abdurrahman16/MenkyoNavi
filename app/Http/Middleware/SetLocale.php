<?php

namespace App\Http\Middleware;

use App\Support\LocalePreference;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function __construct(public LocalePreference $locales) {}

    /**
     * Resolve and activate the visitor's preferred interface language.
     *
     * Preference order: session → authenticated profile → cookie → Japanese default.
     * Browser language and IP geolocation are never used.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->locales->resolve($request);
        $this->locales->activate($locale);

        return $next($request);
    }
}
