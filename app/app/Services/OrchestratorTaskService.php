<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrchestratorTaskService
{
    /**
     * Получить следующую доступную задачу для оркестратора
     * 
     * @param Agent $agent Агент (управляющий или обычный)
     * @return AgentTask|null
     */
    public function getNextAvailableTask(Agent $agent): ?AgentTask
    {
        try {
            return DB::transaction(function () use ($agent) {
                $query = AgentTask::availableForOrchestrator()
                    ->with('project'); // Загружаем связь для public_key

                // Фильтрация по доступу к проектам
                if (!$agent->hasCrossProjectAccess()) {
                    $query->where('project_id', $agent->project_id);
                    
                    Log::debug('Orchestrator: Filtering tasks by project', [
                        'agent_id' => $agent->id,
                        'project_id' => $agent->project_id,
                    ]);
                }

                $task = $query
                    ->orderBy('created_at', 'asc')
                    ->lockForUpdate()
                    ->skipLocked()
                    ->first();

                if ($task) {
                    Log::info('Orchestrator: Task found', [
                        'task_id' => $task->id,
                        'agent_id' => $agent->id,
                        'project_id' => $task->project_id,
                        'has_cross_project_access' => $agent->has_cross_project_access,
                    ]);
                }

                return $task;
            }, 5); // Таймаут транзакции 5 секунд

        } catch (\Exception $e) {
            Log::error('Orchestrator: Failed to get next task', [
                'agent_id' => $agent->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Резервировать задачу для воркера
     * 
     * @param AgentTask $task Задача для резервирования
     * @param Agent $agent Управляющий агент
     * @param int $seconds Количество секунд резервирования
     * @param string $agentUuid UUID воркера
     * @return void
     * @throws \Exception
     */
    public function reserveTask(AgentTask $task, Agent $agent, int $seconds, string $agentUuid): void
    {
        try {
            DB::transaction(function () use ($task, $agent, $seconds, $agentUuid) {
                // Перезагружаем задачу с блокировкой
                $task = AgentTask::where('id', $task->id)
                    ->lockForUpdate()
                    ->first();

                if (!$task) {
                    throw new \Exception('Task not found');
                }

                // Проверка доступа к проекту
                if (!$agent->hasCrossProjectAccess() && $task->project_id !== $agent->project_id) {
                    Log::warning('Orchestrator: Access denied to project', [
                        'agent_id' => $agent->id,
                        'task_id' => $task->id,
                        'task_project_id' => $task->project_id,
                        'agent_project_id' => $agent->project_id,
                    ]);
                    throw new \Exception('Access denied to this project');
                }

                // Проверка активного резервирования
                if ($task->isReserved()) {
                    $message = "Task is already reserved until {$task->reserved_until}";
                    
                    Log::warning('Orchestrator: Reservation conflict', [
                        'task_id' => $task->id,
                        'agent_id' => $agent->id,
                        'reserved_until' => $task->reserved_until,
                        'current_agent_id' => $task->agent_id,
                    ]);
                    
                    throw new \Exception($message);
                }

                // Резервируем задачу
                $task->reserve($seconds, $agent->id, $agentUuid);

                Log::info('Orchestrator: Task reserved', [
                    'task_id' => $task->id,
                    'agent_id' => $agent->id,
                    'agent_uuid' => $agentUuid,
                    'reserved_seconds' => $seconds,
                    'reserved_until' => $task->reserved_until,
                ]);
            }, 5);

        } catch (\Exception $e) {
            Log::error('Orchestrator: Failed to reserve task', [
                'task_id' => $task->id,
                'agent_id' => $agent->id,
                'agent_uuid' => $agentUuid,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Проверка активного резервирования задачи
     */
    public function isTaskReserved(AgentTask $task): bool
    {
        return $task->isReserved();
    }

    /**
     * Получить информацию о конфликте резервирования
     * 
     * @return array|null ['reserved_until' => string, 'agent_id' => int] или null
     */
    public function validateReservationConflict(AgentTask $task): ?array
    {
        if (!$task->isReserved()) {
            return null;
        }

        return [
            'reserved_until' => $task->reserved_until->toIso8601String(),
            'agent_id' => $task->agent_id,
        ];
    }
}

