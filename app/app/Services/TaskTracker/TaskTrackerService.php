<?php

namespace App\Services\TaskTracker;

use App\Interfaces\TaskTrackerInterface;

class TaskTrackerService
{
    /**
     * @var TaskTrackerInterface
     */
    private TaskTrackerInterface $integration;

    /**
     * Конструктор сервиса
     *
     * @param TaskTrackerInterface $integration Активная интеграция с таск-трекером
     */
    public function __construct(TaskTrackerInterface $integration)
    {
        $this->integration = $integration;
    }

    /**
     * Создает задачу через активную интеграцию
     *
     * @param string $title Название задачи
     * @param string $description Описание задачи
     * @return bool Результат создания задачи
     */
    public function createTask(string $title, string $description): bool
    {
        return $this->integration->createTask($title, $description);
    }

    /**
     * Получает активную интеграцию
     *
     * @return TaskTrackerInterface
     */
    public function getIntegration(): TaskTrackerInterface
    {
        return $this->integration;
    }
}
