<?php

namespace App\Http\Controllers;

use App\Support\Content\IntersectionPriorityGuide;
use App\Support\Content\PedestrianPriorityGuide;
use App\Support\Content\RoadSignsGuide;
use App\Support\Content\StudyMethodsGuide;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LearnController extends Controller
{
    public function index(): View
    {
        return view('learn.index');
    }

    public function studyMethods(): View
    {
        $locale = app()->getLocale();

        return view('learn.study-methods', [
            'content' => StudyMethodsGuide::content($locale),
            'manifest' => StudyMethodsGuide::manifest(),
            'sources' => StudyMethodsGuide::sources(),
            'localeStatus' => StudyMethodsGuide::localeStatus($locale),
            'usingFallback' => ! StudyMethodsGuide::hasCompleteContent($locale),
            'jsonLd' => StudyMethodsGuide::jsonLd($locale),
            'robots' => StudyMethodsGuide::robots($locale),
        ]);
    }

    public function roadSigns(Request $request): View
    {
        $locale = app()->getLocale();
        $category = $request->query('category');
        $query = $request->query('q');

        $category = is_string($category) ? $category : null;
        $query = is_string($query) ? $query : null;

        if ($category !== null && $category !== 'all' && ! in_array($category, RoadSignsGuide::CATEGORY_KEYS, true)) {
            $category = null;
        }

        return view('learn.road-signs', [
            'content' => RoadSignsGuide::content($locale),
            'manifest' => RoadSignsGuide::manifest(),
            'sources' => RoadSignsGuide::sources(),
            'localeStatus' => RoadSignsGuide::localeStatus($locale),
            'usingFallback' => ! RoadSignsGuide::hasCompleteContent($locale),
            'jsonLd' => RoadSignsGuide::jsonLd($locale),
            'robots' => RoadSignsGuide::robots($locale),
            'signs' => RoadSignsGuide::filterSigns($category, $query, $locale),
            'allSigns' => RoadSignsGuide::enrichedSigns($locale),
            'activeCategory' => $category ?: 'all',
            'searchQuery' => $query ?? '',
            'practiceItems' => RoadSignsGuide::practiceItems($locale),
        ]);
    }

    public function trafficRules(): View
    {
        return view('learn.traffic-rules');
    }

    public function pedestrianPriority(): View
    {
        $locale = app()->getLocale();
        $content = PedestrianPriorityGuide::content($locale);
        $manifest = PedestrianPriorityGuide::manifest();
        $usingFallback = ! PedestrianPriorityGuide::hasCompleteContent($locale);

        return view('learn.traffic-rules.pedestrian-priority', [
            'content' => $content,
            'manifest' => $manifest,
            'sources' => PedestrianPriorityGuide::sources(),
            'localeStatus' => PedestrianPriorityGuide::localeStatus($locale),
            'usingFallback' => $usingFallback,
            'jsonLd' => PedestrianPriorityGuide::jsonLd($locale),
            'robots' => PedestrianPriorityGuide::robots($locale),
        ]);
    }

    public function intersectionPriority(): View
    {
        $locale = app()->getLocale();

        return view('learn.traffic-rules.intersection-priority', [
            'content' => IntersectionPriorityGuide::content($locale),
            'manifest' => IntersectionPriorityGuide::manifest(),
            'sources' => IntersectionPriorityGuide::sources(),
            'localeStatus' => IntersectionPriorityGuide::localeStatus($locale),
            'usingFallback' => ! IntersectionPriorityGuide::hasCompleteContent($locale),
            'jsonLd' => IntersectionPriorityGuide::jsonLd($locale),
            'robots' => IntersectionPriorityGuide::robots($locale),
        ]);
    }

    public function hazardPrediction(): View
    {
        return view('learn.hazard-prediction');
    }
}
