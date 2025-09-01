<?php

namespace App\Services\AgentTaskManager;

use App\Interfaces\AgentTaskManagerInterface;
use App\Interfaces\Factory\AgentResultHandlerFactoryInterface;
use App\Interfaces\LLM\AgentResultHandlerInterface;
use App\Models\AgentTask;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AgentTaskManagerService implements AgentTaskManagerInterface
{
    /**
     * Создать новую задачу для агента
     */
    public function createTask(
        AgentResultHandlerInterface $handler,
        int $projectId,
        int $chatId
    ): int {
        try {
            $task = AgentTask::create([
                'handler' => $handler->getKey(),
                'handler_options' => $handler->getOptions(),
                'project_id' => $projectId,
                'created_by' => Auth::id(),
                'chat_id' => $chatId,
                'status' => AgentTask::STATUS_WAIT,
            ]);

            Log::info('Agent task created', [
                'task_id' => $task->id,
                'handler' => $handler->getKey(),
                'project_id' => $projectId,
                'chat_id' => $chatId,
                'created_by' => Auth::id(),
            ]);

            return $task->id;
        } catch (\Exception $e) {
            Log::error('Failed to create agent task', [
                'handler' => $handler->getKey(),
                'project_id' => $projectId,
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function assignTaskToAgent(string $agentId): ?AgentTask
    {
        try {
            // Сначала проверяем, есть ли у агента уже назначенная задача
            $existingTask = AgentTask::where('agent_id', $agentId)
                ->where('status', AgentTask::STATUS_PROCESSING)
                ->first();

            if ($existingTask) {
                Log::debug('Agent already has active task', [
                    'agent_id' => $agentId,
                    'task_id' => $existingTask->id,
                ]);
                return $existingTask;
            }

            // Если нет активной задачи, ищем новую с блокировкой
            return DB::transaction(function () use ($agentId) {
                $waitingTask = AgentTask::where('status', AgentTask::STATUS_WAIT)
                    ->whereNull('agent_id')
                    ->orderBy('created_at')
                    ->lockForUpdate()
                    ->first();

                if ($waitingTask) {
                    // Назначаем задачу агенту
                    $waitingTask->update([
                        'agent_id' => $agentId,
                        'status' => AgentTask::STATUS_PROCESSING
                    ]);

                    Log::info('Task assigned to agent', [
                        'task_id' => $waitingTask->id,
                        'agent_id' => $agentId,
                    ]);

                    return $waitingTask->fresh(); // Обновляем модель из БД
                }

                Log::debug('No waiting tasks available for agent', [
                    'agent_id' => $agentId,
                ]);

                return null;
            }, 5); // Таймаут транзакции 5 секунд

        } catch (QueryException $e) {
            Log::error('Database error during task assignment', [
                'agent_id' => $agentId,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            // Повторная попытка для deadlock ошибок
            if ($e->getCode() === '40001' || strpos($e->getMessage(), 'Deadlock') !== false) {
                sleep(rand(1, 3)); // Случайная задержка
                return $this->assignTaskToAgent($agentId); // Рекурсивный вызов
            }

            throw $e;
        }
    }

    public function getNextWaitingTask(): ?AgentTask
    {
        return AgentTask::waiting()
            ->orderBy('created_at')
            ->first();
    }

    public function markAsProcessing(int $taskId): void
    {
        $task = AgentTask::findOrFail($taskId);
        $task->update(['status' => AgentTask::STATUS_PROCESSING]);

        Log::info('Task marked as processing', ['task_id' => $taskId]);
    }

    public function markAsCompleted(int $taskId): void
    {
        $task = AgentTask::findOrFail($taskId);
        $task->update(['status' => AgentTask::STATUS_SUCCESS]);

        Log::info('Task marked as completed', ['task_id' => $taskId]);
    }

    public function markAsFailed(int $taskId): void
    {
        $task = AgentTask::findOrFail($taskId);
        $task->update(['status' => AgentTask::STATUS_FAILED]);

        Log::error('Task marked as failed', ['task_id' => $taskId]);
    }

    public function getAgentTasks(string $agentId, string $status = null): Collection
    {
        $query = AgentTask::forAgent($agentId);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->with(['project', 'creator', 'llmChat'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    public function resetStuckTasks(int $minutesStuck = 60): int
    {
        try {
            $stuckTasks = AgentTask::stuck($minutesStuck)->get();
            $resetCount = 0;

            foreach ($stuckTasks as $task) {
                $task->update([
                    'status' => AgentTask::STATUS_WAIT,
                    'agent_id' => null
                ]);
                $resetCount++;

                Log::warning('Stuck task reset to waiting', [
                    'task_id' => $task->id,
                    'previous_agent_id' => $task->agent_id,
                    'stuck_minutes' => now()->diffInMinutes($task->updated_at),
                ]);
            }

            if ($resetCount > 0) {
                Log::info('Reset stuck tasks', [
                    'count' => $resetCount,
                    'minutes_threshold' => $minutesStuck,
                ]);
            }

            return $resetCount;
        } catch (\Exception $e) {
            Log::error('Failed to reset stuck tasks', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
