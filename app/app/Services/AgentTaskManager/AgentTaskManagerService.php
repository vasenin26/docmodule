<?php

namespace App\Services\AgentTaskManager;

use App\Common\Enums\AgentTaskType;
use App\Interfaces\AgentTaskManagerInterface;
use App\Interfaces\Factory\AgentResultHandlerFactoryInterface;
use App\Interfaces\LLM\AgentResultHandlerInterface;
use App\Models\Agent;
use App\Models\AgentTask;
use App\Models\LLMChat;
use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Pricing\PricingService;

class AgentTaskManagerService implements AgentTaskManagerInterface
{
    /**
     * Pricing service used to calculate task costs based on GenerationModel
     */
    protected PricingService $pricingService;

    public function __construct(PricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    /**
     * Создать новую задачу для агента
     */
    public function createTask(
        AgentResultHandlerInterface $handler,
        int $creatorId,
        int $projectId,
        int $chatId,
        bool $resultRequired = true,
        AgentTaskType $type = AgentTaskType::TEXT
    ): int {
        try {
            $project = Project::find($projectId);
            $preferredModel = $project?->getGenerationModelNameForType($type->value);
            $task = AgentTask::create([
                'type' => $type,
                'handler' => $handler::class,
                'handler_options' => $handler->getOptions(),
                'project_id' => $projectId,
                'created_by' => $creatorId,
                'chat_id' => $chatId,
                'status' => AgentTask::STATUS_WAIT,
                'result_required' => $resultRequired,
                'agent_model' => $preferredModel,
            ]);

            Log::info('Agent task created', [
                'task_id' => $task->id,
                'type' => $type->value,
                'handler' => $handler::class,
                'project_id' => $projectId,
                'chat_id' => $chatId,
                'created_by' => $creatorId,
                'result_required' => $resultRequired,
                'agent_model' => $preferredModel,
            ]);

            return $task->id;
        } catch (\Exception $e) {
            Log::error('Failed to create agent task', [
                'handler' => $handler::class,
                'type' => $type->value,
                'project_id' => $projectId,
                'chat_id' => $chatId,
                'result_required' => $resultRequired,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function assignTaskToAgent(Agent $agent, string $agentId): ?AgentTask
    {
        try {
            // Сначала проверяем, есть ли у агента уже назначенная задача
            $existingTask = AgentTask::where('agent_uuid', $agentId)
                ->whereNull('parent_id')
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
            return DB::transaction(function () use ($agentId, $agent) {
                // Используем единую логику фильтрации задач
                $waitingTask = AgentTask::availableForOrchestrator()
                    ->where('project_id', $agent->project_id)  // Обычные агенты - только свой проект
                    ->orderBy('created_at')
                    ->lock('FOR UPDATE SKIP LOCKED')
                    ->first();

                if ($waitingTask) {
                    // Назначаем задачу агенту
                    $waitingTask->update([
                        'agent_id' => $agent->id,
                        'agent_uuid' => $agentId,
                        'status' => AgentTask::STATUS_PROCESSING
                    ]);

                    Log::info('Task assigned to agent', [
                        'task_id' => $waitingTask->id,
                        'agent_id' => $agent->id,
                        'agent_uuid' => $agentId,
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
                return $this->assignTaskToAgent($agent, $agentId); // Рекурсивный вызов
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

    public function stopTask(int $id): void
    {
        $task = AgentTask::findOrFail($id);

        // Останавливаем основную задачу
        $task->update(['status' => AgentTask::STATUS_SUCCESS]);

        // Останавливаем все подзадачи
        $subtasks = $task->children()
            ->where('status', AgentTask::STATUS_PROCESSING)
            ->get();

        if ($subtasks->isNotEmpty()) {
            $subtaskIds = $subtasks->pluck('id')->toArray();
            AgentTask::whereIn('id', $subtaskIds)
                ->update(['status' => AgentTask::STATUS_SUCCESS]);

            Log::info('Stopped task and its subtasks', [
                'task_id' => $id,
                'subtask_ids' => $subtaskIds,
                'subtasks_count' => count($subtaskIds),
            ]);
        } else {
            Log::info('Stopped task (no active subtasks)', [
                'task_id' => $id,
            ]);
        }
    }

    /**
     * Получить задачу по идентификатору, принадлежит ли она указанному агенту и UUID
     */
    public function getTaskForAgentById(Agent $agent, string $agentUuid, int $taskId): ?AgentTask
    {
        return AgentTask::where('id', $taskId)
            ->where('agent_id', $agent->id)
            ->where('agent_uuid', $agentUuid)
            ->with('llmChat')
            ->first();
    }

    /**
     * Создать новую подзадачу для существующей задачи
     */
    public function createSubtask(int $parentTaskId, int $agentId, string $agentUuid, string $type): int
    {
        try {
            // Найти родительскую задачу
            $parent = AgentTask::findOrFail($parentTaskId);
            $projectId = $parent->project_id;
            $creatorId = $parent->created_by;

            // Создаём новый пустой чат
            $chat = LLMChat::create([
                'messages' => [],
                'context_fill' => null,
            ]);

            // Определить preferred model через проект
            $project = Project::find($projectId);
            $preferredModel = $project?->getGenerationModelNameForType($type);

            // Создать подзадачу
            $task = AgentTask::create([
                'type' => $type,
                'handler' => null,
                'handler_options' => [],
                'project_id' => $projectId,
                'created_by' => $creatorId,
                'parent_id' => $parentTaskId,
                'chat_id' => $chat->id,
                'status' => AgentTask::STATUS_PROCESSING,
                'result_required' => false,
                'agent_model' => $preferredModel,
                'agent_id' => $agentId,
                'agent_uuid' => $agentUuid,
            ]);

            Log::info('Agent subtask created', [
                'subtask_id' => $task->id,
                'parent_id' => $parentTaskId,
                'agent_id' => $agentId,
                'agent_uuid' => $agentUuid,
                'type' => $type,
                'project_id' => $projectId,
                'handler' => null,
            ]);

            return $task->id;
        } catch (\Exception $e) {
            Log::error('Failed to create agent subtask', [
                'parent_task_id' => $parentTaskId,
                'agent_id' => $agentId,
                'agent_uuid' => $agentUuid,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
