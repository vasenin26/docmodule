<?php

namespace App\Interfaces;

use App\Common\Enums\AgentTaskType;
use App\Interfaces\LLM\AgentResultHandlerInterface;
use App\Models\Agent;
use App\Models\AgentTask;

interface AgentTaskManagerInterface
{
    /**
     * Создать новую задачу для агента
     *
     * @param AgentResultHandlerInterface $handler Обработчик результата
     * @param int $creatorId ID создателя задачи
     * @param int $projectId ID проекта
     * @param int $chatId ID чата LLM
     * @param bool $resultRequired Требуется ли результат для задачи
     * @param AgentTaskType $type Тип задачи агента
     * @return int ID созданной задачи
     * @throws \InvalidArgumentException Если переданы некорректные параметры
     */
    public function createTask(
        AgentResultHandlerInterface $handler,
        int $creatorId,
        int $projectId,
        int $chatId,
        bool $resultRequired = true,
        AgentTaskType $type = AgentTaskType::TEXT
    ): int;

    /**
     * Назначить задачу агенту (thread-safe)
     * Возвращает уже назначенную задачу или назначает новую из очереди
     *
     * @param string $agentId UUID агента
     * @return AgentTask|null Назначенная задача или null если очередь пуста
     * @throws \Illuminate\Database\QueryException При ошибках БД
     */
    public function assignTaskToAgent(Agent $agent, string $agentId): ?AgentTask;

    /**
     * Получить следующую ожидающую задачу из очереди (без назначения)
     *
     * @return AgentTask|null Задача в статусе wait или null
     */
    public function getNextWaitingTask(): ?AgentTask;

    /**
     * Отметить задачу как выполняющуюся
     *
     * @param int $taskId ID задачи
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function markAsProcessing(int $taskId): void;

    /**
     * Отметить задачу как успешно завершенную
     *
     * @param int $taskId ID задачи
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function markAsCompleted(int $taskId): void;

    /**
     * Отметить задачу как неудачную
     *
     * @param int $taskId ID задачи
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function markAsFailed(int $taskId): void;

    /**
     * Получить задачи агента в определенном статусе
     *
     * @param string $agentId UUID агента
     * @param string $status Статус задачи
     * @return \Illuminate\Database\Eloquent\Collection<AgentTask>
     */
    public function getAgentTasks(string $agentId, string $status = null): \Illuminate\Database\Eloquent\Collection;

    /**
     * Сбросить зависшие задачи в статус wait
     *
     * @param int $minutesStuck Количество минут без активности
     * @return int Количество сброшенных задач
     */
    public function resetStuckTasks(int $minutesStuck = 60): int;
}
