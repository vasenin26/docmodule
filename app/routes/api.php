<?php

use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\PageController;
use Illuminate\Support\Facades\Route;

Route::get('health', function () {
    return 'ok';
});

Route::prefix('agent')->name('agent.')->middleware(['agent.jwt'])->group(function () {
    Route::post('task', [TaskController::class, 'getTask'])->name('task.get');
    Route::put('task/{id}', [TaskController::class, 'updateTask'])->name('task.update');
    Route::get('task/{id}', [TaskController::class, 'getTaskById'])->name('task.show');
    Route::put('task/{id}/process', [TaskController::class, 'processTask'])->name('task.process');

    // Page API routes
    Route::get('page/version/{id}', [PageController::class, 'getPageVersion'])->name('page.version');
    Route::get('page/{id}', [PageController::class, 'getPage'])->name('page.get');
    Route::get('pages', [PageController::class, 'getPages'])->name('pages.list');
    Route::get('pages/hierarchy', [PageController::class, 'getPageHierarchy'])->name('pages.hierarchy');
    Route::get('page/{id}/children', [PageController::class, 'getPageChildren'])->name('page.children');
    Route::get('page/{id}/parent', [PageController::class, 'getPageParent'])->name('page.parent');
    Route::get('page/{id}/related', [PageController::class, 'getRelatedPages'])->name('page.related');
    Route::get('page/{id}/actualization', [PageController::class, 'getPageActualization'])->name('page.actualization');
    Route::get('page/{id}/files', [PageController::class, 'getPageFiles'])->name('page.files');
    Route::get('page/{id}/tasks', [PageController::class, 'getPageTasks'])->name('page.tasks');
});

Route::prefix('admin/agent')->middleware(['auth', 'admin'])->group(function () {

    Route::get('stats', function () {
        return response()->json([
            'waiting' => \App\Models\AgentTask::where('status', 'wait')->count(),
            'processing' => \App\Models\AgentTask::where('status', 'processing')->count(),
            'completed' => \App\Models\AgentTask::where('status', 'success')->count(),
            'failed' => \App\Models\AgentTask::where('status', 'failed')->count(),
        ]);
    })->name('admin.agent.stats');

    Route::get('stuck', function () {
        $stuckTasks = \App\Models\AgentTask::stuck(30)->with(['project', 'creator'])->get();
        return response()->json($stuckTasks);
    })->name('admin.agent.stuck');

    Route::post('reset-stuck', function () {
        $resetCount = app(\App\Services\AgentTaskManager\AgentTaskManagerService::class)->resetStuckTasks(60);
        return response()->json(['reset_count' => $resetCount]);
    })->name('admin.agent.reset-stuck');

});

// Public API for techplanes
Route::post('techplanes/{techplane}/done', [\App\Http\Controllers\Api\TechplaneController::class, 'markDone'])
    ->name('api.techplanes.done');

// Orchestrator API routes
Route::prefix('orchestrator')
    ->name('orchestrator.')
    ->middleware(['orchestrator.auth'])
    ->group(function () {
        Route::get('tasks/next', [\App\Http\Controllers\Api\OrchestratorController::class, 'getNextTask'])
            ->name('tasks.next');

        Route::post('tasks/{taskId}/reserve', [\App\Http\Controllers\Api\OrchestratorController::class, 'reserveTask'])
            ->name('tasks.reserve');

        Route::put('projects/{projectId}/key', [\App\Http\Controllers\Api\OrchestratorController::class, 'updateProjectKey'])
            ->name('projects.key.update');
    });
