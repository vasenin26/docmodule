<?php

use App\Http\Controllers\Api\AgentController;
use Illuminate\Support\Facades\Route;

Route::prefix('agent')->name('agent.')->middleware(['agent.jwt'])->group(function () {
    Route::post('task', [AgentController::class, 'getTask'])->name('task.get');
    Route::put('task/{id}', [AgentController::class, 'updateTask'])->name('task.update');
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
