<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\TaskController;
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
    
    // Новые маршруты для черновиков
    Route::post('pages/{page}/draft/approve', [PageController::class, 'approveDraft'])->name('pages.draft.approve');
    Route::get('pages/{page}/draft', [PageController::class, 'getDraft'])->name('pages.draft.get');
    Route::delete('pages/{page}/draft', [PageController::class, 'deleteDraft'])->name('pages.draft.delete');
    
    // Маршрут для создания задачи
    Route::post('pages/{page}/create-task', [PageController::class, 'createTask'])->name('pages.create-task');
    
    // Маршруты для задач
    Route::get('tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
    Route::get('tasks/{task}/status', [TaskController::class, 'checkGenerationStatus'])->name('tasks.status');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
