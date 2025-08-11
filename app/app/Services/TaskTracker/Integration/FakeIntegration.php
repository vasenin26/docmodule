<?php

namespace App\Services\TaskTracker\Integration;

use App\Interfaces\TaskTrackerInterface;
use Illuminate\Support\Facades\Log;

class FakeIntegration implements TaskTrackerInterface
{
    /**
     * Создает задачу в таск-трекере (имитация)
     *
     * @param string $title Название задачи
     * @param string $description Описание задачи
     * @return bool Возвращает true для имитации успешного создания
     */
    public function createTask(string $title, string $description): bool
    {
        Log::info('FakeIntegration: Создание задачи', [
            'title' => $title,
            'description' => $description,
            'timestamp' => now()->toISOString(),
        ]);

        return true;
    }
}
