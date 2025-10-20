<?php

namespace App\Http\Controllers\Api;

use App\Common\DTO\AgentTaskUpdateDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\GetTaskDetailsRequest;
use App\Http\Requests\Agent\GetTaskRequest;
use App\Http\Requests\Agent\UpdateTaskRequest;
use App\Interfaces\Factory\AgentResultHandlerFactoryInterface;
use App\Models\AgentTask;
use App\Services\AgentTaskManager\AgentTaskManagerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TaskController extends Controller
{
    public function __construct(
        private readonly AgentTaskManagerService $taskManager
    )
    {
    }

    /**
     * Получить задачу для выполнения агентом
     * POST /api/agent/task
     */
    public function getTask(GetTaskRequest $request): JsonResponse
    {
        $agent = $request->get('agent'); // Получаем агента из middleware
        $agentUuid = $request->getAgentUuid(); // Получаем UUID от клиента

        try {
            Log::info($agentUuid);

            $task = $this->taskManager->assignTaskToAgent($agent, $agentUuid);

            if (!$task) {
                return response()->json([
                    'task_id' => null,
                    'message' => 'No tasks available'
                ], 404);
            }

            // Записываем agent_uuid от клиента и agent_id для связи с Agent
            $task->update([
                'agent_uuid' => $agentUuid,
                'agent_id' => $agent->id,
            ]);

            Log::info('Task assigned via API', [
                'task_id' => $task->id,
                'agent_id' => $agent->id,
                'agent_uuid' => $agentUuid,
                'handler' => $task->handler,
            ]);

        } catch (\Exception $e) {
            Log::error('API: Failed to assign task to agent', [
                'agent_id' => $agent->id,
                'agent_uuid' => $agentUuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Service temporarily unavailable',
                'message' => 'Please try again later'
            ], 503);
        }

        $task->loadMissing(['project', 'llmChat']);
        return response()->json([
            'id' => $task->id,
            'type' => $task->type->value,
            'agent_uuid' => $task->agent_uuid,
            'project_id' => $task->project_id,
            'result_required' => $task->result_required,
            'agent_model' => $task->agent_model,
            'context_id' => $task->getContextId(),
            'chat' => [
                'id' => $task->llmChat->id,
                'messages' => $task->llmChat->messages ?? [],
                'total_tokens' => $task->total_tokens,
                'context_fill' => $task->llmChat->context_fill,
            ]
        ]);
    }

    /**
     * Обновить состояние задачи
     * PUT /api/agent/task/{id}
     */
    public function updateTask(AgentResultHandlerFactoryInterface $handlerFactory, UpdateTaskRequest $request, int $id): JsonResponse
    {
        $agent = $request->get('agent'); // Получаем агента из middleware

        try {
            $agentTask = AgentTask::where('id', $id)
                ->where('agent_uuid', $request->getAgentUuid()) // Проверяем по UUID от клиента
                ->where('agent_id', $agent->id) // Дополнительная проверка принадлежности агенту
                ->first();

            if (!$agentTask) {
                $this->logSuspiciousActivity($request, 'task_update_denied', [
                    'requested_task_id' => $id,
                    'agent_id' => $agent->id,
                    'agent_uuid' => $request->getAgentUuid(),
                    'reason' => 'task_not_found_or_not_owned'
                ]);

                return response()->json([
                    'error' => 'Task not found, not assigned to this agent, or not in processing state'
                ], 404);
            }

            if ($agentTask->status === AgentTask::STATUS_SUCCESS) {
                return response()->json([
                    'status' => 'stopped',
                    'message' => 'Task was already stopped'
                ]);
            }

            $updateData = AgentTaskUpdateDTO::fromArray([
                'chat' => $request->getChatMessages(),
                'stats' => $request->getTokenStats(),
                'result' => $request->getResult(),
                'context_fill' => $request->getContextFill(),
                'model' => $request->input('model') ?? null,
            ]);

            $chat = $agentTask->llmChat;
            $chat->update([
                'messages' => $updateData->chat,
                'context_fill' => $updateData->context_fill ?? $chat->context_fill,
            ]);

            $agentTask->update([
                'prompt_tokens' => $updateData->stats->prompt_tokens ?? $agentTask->prompt_tokens,
                'completion_tokens' => $updateData->stats->completion_tokens ?? $agentTask->completion_tokens,
                'total_tokens' => $updateData->stats->total_tokens ?? $agentTask->total_tokens,
            ]);

            $handlerFactory->createTaskHandler($agentTask)?->handleResult($updateData->result);

            $taskUpdates = [];

            if ($request->isCompleted()) {
                $taskUpdates['status'] = AgentTask::STATUS_SUCCESS;
            }

            if ($updateData->model !== null) {
                $taskUpdates['agent_model'] = $updateData->model;
            }

            if (!empty($taskUpdates)) {
                $agentTask->update($taskUpdates);
            }

            return response()->json([
                'status' => 'updated',
                'message' => 'Task progress saved'
            ]);

        } catch (\Exception $e) {
            Log::error('API: Failed to update task', [
                'task_id' => $id,
                'agent_id' => $agent->id,
                'agent_uuid' => $request->getAgentUuid(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to update task'
            ], 500);
        }
    }

    /**
     * Получить подробную информацию о задаче по её идентификатору
     * GET /api/agent/task/{id}
     */
    public function getTaskById(GetTaskDetailsRequest $request, int $id): JsonResponse
    {
        $agent = $request->get('agent');
        $agentUuid = $request->getAgentUuid();

        $task = $this->taskManager->getTaskForAgentById($agent, $agentUuid, $id);

        if (!$task) {
            $this->logSuspiciousActivity($request, 'task_read_denied', [
                'requested_task_id' => $id,
                'agent_id' => $agent->id,
                'agent_uuid' => $agentUuid,
                'reason' => 'task_not_found_or_not_owned',
            ]);

            return response()->json([
                'task_id' => null,
                'message' => 'Task not found'
            ], 404);
        }

        return response()->json([
            'id' => $task->id,
            'type' => $task->type->value,
            'agent_uuid' => $task->agent_uuid,
            'project_id' => $task->project_id,
            'result_required' => $task->result_required,
            'agent_model' => $task->agent_model,
            'context_id' => $task->getContextId(),
            'chat' => [
                'id' => $task->llmChat->id,
                'messages' => $task->llmChat->messages ?? [],
                'total_tokens' => $task->total_tokens,
                'context_fill' => $task->llmChat->context_fill,
            ]
        ]);
    }

    /**
     * Подтвердить перевод задачи в состояние processing
     * PUT /api/agent/task/{id}/process
     */
    public function processTask(GetTaskDetailsRequest $request, int $id): JsonResponse
    {
        $agent = $request->get('agent');
        $agentUuid = $request->getAgentUuid();

        $task = $this->taskManager->getTaskForAgentById($agent, $agentUuid, $id);

        if (!$task) {
            $this->logSuspiciousActivity($request, 'task_process_denied', [
                'requested_task_id' => $id,
                'agent_id' => $agent->id,
                'agent_uuid' => $agentUuid,
                'reason' => 'task_not_found_or_not_owned',
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Task not found'
            ], 404);
        }

        if ($task->status !== AgentTask::STATUS_PROCESSING) {
            $task->update(['status' => AgentTask::STATUS_PROCESSING]);
        }

        return response()->json([
            'status' => 'processing',
            'message' => 'Task marked as processing'
        ]);
    }

    private function logSuspiciousActivity($request, string $action, array $context = []): void
    {
        Log::warning("Suspicious agent activity: {$action}", array_merge([
            'agent_id' => $request->get('agent')?->id,
            'agent_uuid' => $request->getAgentUuid(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'timestamp' => now()->toISOString(),
        ], $context));
    }
}
