<?php

use App\Http\Controllers\AdsTxtController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamAttemptController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\GuideController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LearnController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MistakeVaultController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Livewire\Admin\DuplicatePairReview;
use App\Livewire\Admin\OriginalKarimenExamPreview;
use App\Livewire\Admin\OriginalKarimenPilotReviewIndex;
use App\Livewire\Admin\OriginalKarimenPilotReviewShow;
use App\Livewire\Admin\QuestionBankBulkClassification;
use App\Livewire\Admin\QuestionBankDashboard;
use App\Livewire\Admin\QuestionBankIndex;
use App\Livewire\Admin\QuestionBankShow;
use Illuminate\Support\Facades\Route;

/*
| Phase 1 serves English at the site root.
| Future multilingual SEO can wrap public routes in locale prefixes such as
| /en/, /ja/, /zh/, /vi/, /tl/, /ne/ once reviewed translations exist.
| Do not emit hreflang for untranslated variants.
*/

Route::get('/', HomeController::class)->name('home');

Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/ads.txt', AdsTxtController::class)->name('ads-txt');

Route::post('/locale/{locale}', [LocaleController::class, 'switch'])
    ->name('locale.switch')
    ->middleware('throttle:30,1');

/*
|--------------------------------------------------------------------------
| Exams
|--------------------------------------------------------------------------
*/
Route::prefix('exams')->name('exams.')->group(function () {
    Route::get('/', [ExamController::class, 'index'])->name('index');
    Route::get('/gaimen-kirikae', [ExamController::class, 'gaimenKirikae'])->name('gaimen-kirikae');
    Route::get('/karimen', [ExamController::class, 'karimen'])->name('karimen');
    Route::get('/honmen', [ExamController::class, 'honmen'])->name('honmen');
    Route::get('/layout-preview', [ExamController::class, 'layoutPreview'])->name('layout-preview');
});

/*
|--------------------------------------------------------------------------
| Learn
|--------------------------------------------------------------------------
*/
Route::prefix('learn')->name('learn.')->group(function () {
    Route::get('/', [LearnController::class, 'index'])->name('index');
    Route::get('/study-methods', [LearnController::class, 'studyMethods'])->name('study-methods');
});

Route::get('/road-signs', [LearnController::class, 'roadSigns'])->name('learn.road-signs');
Route::get('/traffic-rules', [LearnController::class, 'trafficRules'])->name('learn.traffic-rules');
Route::get('/traffic-rules/pedestrian-priority', [LearnController::class, 'pedestrianPriority'])
    ->name('learn.traffic-rules.pedestrian-priority');
Route::get('/traffic-rules/intersection-priority', [LearnController::class, 'intersectionPriority'])
    ->name('learn.traffic-rules.intersection-priority');
Route::get('/hazard-prediction', [LearnController::class, 'hazardPrediction'])->name('learn.hazard-prediction');

/*
|--------------------------------------------------------------------------
| Guides
|--------------------------------------------------------------------------
*/
Route::prefix('guides')->name('guides.')->group(function () {
    Route::get('/', [GuideController::class, 'index'])->name('index');
    Route::get('/foreign-license-conversion', [GuideController::class, 'foreignLicenseConversion'])->name('foreign-license-conversion');
    Route::get('/required-documents', [GuideController::class, 'requiredDocuments'])->name('required-documents');
});

Route::get('/exam-centers', [GuideController::class, 'examCenters'])->name('guides.exam-centers');

/*
|--------------------------------------------------------------------------
| Trust & legal
|--------------------------------------------------------------------------
*/
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/editorial-policy', [PageController::class, 'editorialPolicy'])->name('editorial-policy');
Route::get('/sources', [PageController::class, 'sources'])->name('sources');
Route::get('/privacy-policy', [PageController::class, 'privacyPolicy'])->name('privacy-policy');
Route::get('/terms-of-service', [PageController::class, 'termsOfService'])->name('terms-of-service');
Route::get('/disclaimer', [PageController::class, 'disclaimer'])->name('disclaimer');
Route::get('/cookie-policy', [PageController::class, 'cookiePolicy'])->name('cookie-policy');
Route::get('/advertising-disclosure', [PageController::class, 'advertisingDisclosure'])->name('advertising-disclosure');

Route::get('/contact', [ContactController::class, 'create'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.store');

/*
|--------------------------------------------------------------------------
| Authenticated area
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/mistakes', [MistakeVaultController::class, 'index'])->name('mistakes.index');

    Route::get('/exams/attempts', [ExamAttemptController::class, 'index'])->name('exams.attempts.index');
    Route::post('/exams/{examType}/attempts', [ExamAttemptController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('exams.attempts.store');
    Route::get('/exams/attempts/{attempt}', [ExamAttemptController::class, 'show'])->name('exams.attempts.show');
    Route::post('/exams/attempts/{attempt}/submit', [ExamAttemptController::class, 'submit'])
        ->middleware('throttle:10,1')
        ->name('exams.attempts.submit');
    Route::get('/exams/attempts/{attempt}/results', [ExamAttemptController::class, 'results'])->name('exams.attempts.results');
    Route::get('/exams/attempts/{attempt}/review', [ExamAttemptController::class, 'review'])->name('exams.attempts.review');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Internal editorial administration
|--------------------------------------------------------------------------
|
| Authorize a local account with:
|   php artisan menkyo:grant-admin you@example.com
| Revoke with:
|   php artisan menkyo:revoke-admin you@example.com
|
| Do not hardcode administrator emails or passwords.
*/
Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin/questions')
    ->name('admin.questions.')
    ->group(function () {
        Route::get('/', QuestionBankDashboard::class)->name('dashboard');
        Route::get('/browse', QuestionBankIndex::class)->name('index');
        Route::get('/bulk-classification', QuestionBankBulkClassification::class)->name('bulk-classification');
        Route::get('/duplicates', DuplicatePairReview::class)->name('duplicates');
        Route::get('/duplicates/{review}', DuplicatePairReview::class)->name('duplicates.show');
        Route::get('/original-karimen-pilot', OriginalKarimenPilotReviewIndex::class)
            ->name('original-karimen-pilot');
        Route::get('/original-karimen-exam-preview', OriginalKarimenExamPreview::class)
            ->name('original-karimen-exam-preview');
        Route::get('/original-karimen-pilot/{entry}', OriginalKarimenPilotReviewShow::class)
            ->name('original-karimen-pilot.show');
        Route::get('/{entry}', QuestionBankShow::class)->name('show');
    });

require __DIR__.'/auth.php';
