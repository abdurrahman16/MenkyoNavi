<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class GuideController extends Controller
{
    public function index(): View
    {
        return view('guides.index');
    }

    public function foreignLicenseConversion(): View
    {
        return view('guides.foreign-license-conversion');
    }

    public function requiredDocuments(): View
    {
        return view('guides.required-documents');
    }

    public function examCenters(): View
    {
        return view('guides.exam-centers');
    }
}
