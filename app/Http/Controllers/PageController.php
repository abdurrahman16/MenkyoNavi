<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PageController extends Controller
{
    public function about(): View
    {
        return view('pages.about');
    }

    public function faq(): View
    {
        return view('pages.faq');
    }

    public function editorialPolicy(): View
    {
        return view('pages.editorial-policy');
    }

    public function sources(): View
    {
        return view('pages.sources');
    }

    public function privacyPolicy(): View
    {
        return view('legal.privacy-policy');
    }

    public function termsOfService(): View
    {
        return view('legal.terms-of-service');
    }

    public function disclaimer(): View
    {
        return view('legal.disclaimer');
    }

    public function cookiePolicy(): View
    {
        return view('legal.cookie-policy');
    }

    public function advertisingDisclosure(): View
    {
        return view('legal.advertising-disclosure');
    }
}
