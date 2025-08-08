<?php

use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Маршруты для страниц документации
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('pages', PageController::class);
    Route::get('pages/{page}/versions', [PageController::class, 'versions'])->name('pages.versions');
    Route::post('pages/{page}/restore/{version}', [PageController::class, 'restore'])->name('pages.restore');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
