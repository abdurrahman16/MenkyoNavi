<?php

namespace App\Http\Controllers;

use App\Services\Exams\QuestionSelectionService;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function __construct(private QuestionSelectionService $selection) {}

    public function index(): View
    {
        return view('exams.index');
    }

    public function gaimenKirikae(): View
    {
        return view('exams.gaimen-kirikae', [
            'practice' => $this->selection->practiceOffer('gaimen-kirikae', auth()->user()),
        ]);
    }

    public function karimen(): View
    {
        return view('exams.karimen', [
            'practice' => $this->selection->practiceOffer('karimen', auth()->user()),
        ]);
    }

    public function honmen(): View
    {
        return view('exams.honmen', [
            'practice' => $this->selection->practiceOffer('honmen', auth()->user()),
        ]);
    }

    public function layoutPreview(): View
    {
        abort_unless(
            (bool) config('ads.layout_preview') && ! app()->isProduction(),
            404
        );

        return view('exams.layout-preview');
    }
}
