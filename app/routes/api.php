<?php

use App\Http\Controllers\Api\AgentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes для агентов
|--------------------------------------------------------------------------
|
| Маршруты для взаимодействия внешних LLM агентов с системой
| Все маршруты используют префикс /api/agent
|
*/

Route::prefix('agent')->name('agent.')->group(function () {
    
    // Получить задачу для выполнения
    Route::post('task', [AgentController::class, 'getTask'])
        ->name('task.get')
        ->middleware(['throttle:60,1']); // Лимит 60 запросов в минуту
    
    // Получить детали конкретной задачи
    Route::get('task/{id}', [AgentController::class, 'getTaskDetails'])
        ->name('task.details')
        ->where('id', '[0-9]+') // Только числовые ID
        ->middleware(['throttle:120,1']); // Лимит 120 запросов в минуту
    
    // Обновить состояние задачи
    Route::put('task/{id}', [AgentController::class, 'updateTask'])
        ->name('task.update')
        ->where('id', '[0-9]+') // Только числовые ID
        ->middleware(['throttle:120,1']); // Лимит 120 запросов в минуту
    
});

/*
|--------------------------------------------------------------------------
| Дополнительные маршруты для мониторинга (опционально)
|--------------------------------------------------------------------------
*/

// Маршруты для административного мониторинга (добавить в будущем)
Route::prefix('admin/agent')->middleware(['auth', 'admin'])->group(function () {
    
    // Статистика по задачам
    Route::get('stats', function () {
        return response()->json([
            'waiting' => \App\Models\AgentTask::where('status', 'wait')->count(),
            'processing' => \App\Models\AgentTask::where('status', 'processing')->count(),
            'completed' => \App\Models\AgentTask::where('status', 'success')->count(),
            'failed' => \App\Models\AgentTask::where('status', 'failed')->count(),
        ]);
    })->name('admin.agent.stats');
    
    // Список зависших задач
    Route::get('stuck', function () {
        $stuckTasks = \App\Models\AgentTask::stuck(30)->with(['project', 'creator'])->get();
        return response()->json($stuckTasks);
    })->name('admin.agent.stuck');
    
    // Сброс зависших задач
    Route::post('reset-stuck', function () {
        $resetCount = app(\App\Services\AgentTaskManagerService::class)->resetStuckTasks(60);
        return response()->json(['reset_count' => $resetCount]);
    })->name('admin.agent.reset-stuck');
    
});
