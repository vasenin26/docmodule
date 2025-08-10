<?php

namespace App\Services\TaskTracker;

interface TaskTrackerInterface
{
    /**
     * Создает задачу в таск-трекере
     *
     * @param string $title Название задачи
     * @param string $description Описание задачи
     * @return bool Возвращает true при успешном создании задачи
     */
    public function createTask(string $title, string $description): bool;
}
