<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SchoolResultsController;
use App\Models\SchoolResults;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Auth/Login', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/clear-cache', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:cache');
    Artisan::call('route:cache');
    Artisan::call('view:clear');
    return "All cache cleared";
});

// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Search
    Route::get('/nearby', [SchoolResultsController::class, 'getNearbyList'])->name('results.getNearBy');
    Route::get('/search-page', [SchoolResultsController::class, 'searchPage'])->name('search.page');
    Route::get('/search', [SchoolResultsController::class, 'axiosGoogleRequest'])->name('results.axiosGoogleRequest');
    Route::get('/getSchoolResultById/{id}', [SchoolResultsController::class, 'getSchoolResultById'])->name('results.getSchoolResultById');
    Route::patch('/update', [SchoolResultsController::class, 'update'])->name('results.updateSchoolResultById');

    Route::get('/scrapeSchoolData', [SchoolResultsController::class, 'scrapeSchoolData']);
    Route::patch('/setComplete/{id}', [SchoolResultsController::class, 'setComplete']);
    // Route::patch('/getHere', [SchoolResultsController::class, 'getHere']);

    Route::get('/my-reports', [ReportController::class, 'index'])->name("report.index");
});

require __DIR__ . '/auth.php';
