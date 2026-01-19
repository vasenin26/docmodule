<?php

namespace App\Http\Controllers\Api;

use App\Common\DTO\AgentSubtaskCreateDTO;
use App\Common\DTO\AgentTaskUpdateDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\CreateSubtaskRequest;
use App\Http\Requests\Agent\GetTaskDetailsRequest;
use App\Http\Requests\Agent\GetTaskRequest;
use App\Http\Requests\Agent\UpdateTaskRequest;
use App\Interfaces\Factory\AgentResultHandlerFactoryInterface;
use App\Models\AgentTask;
use App\Services\AgentTaskManager\AgentTaskManagerService;
use App\Services\Pricing\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\LLMChat;

class TaskController extends Controller
{
    public function __construct(
        private readonly AgentTaskManagerService $taskManager,
        private readonly PricingService $pricingService
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
            'type' => $task->type,
            'agent_uuid' => $task->agent_uuid,
            'project_id' => $task->project_id,
            'result_required' => $task->result_required,
            'agent_model' => $task->agent_model,
            'context_id' => $task->getContextId(),
            'chat' => $task->llmChat?->toApiArray(),
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

            if ($agentTask->status !== AgentTask::STATUS_PROCESSING) {
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
                // Берём только провалидированные значения
                'model' => $request->validated('model', null),
                'context' => $request->validated('context', null),
            ]);

            // У некоторых (старых/особых) задач может не быть чата — создаём для обратной совместимости
            $chat = $agentTask->llmChat;
            if (!$chat) {
                $chat = LLMChat::create([
                    'project_id' => $agentTask->project_id,
                    'messages' => [],
                    'context' => [],
                    'context_fill' => null,
                ]);
                $agentTask->update(['chat_id' => $chat->id]);
            }

            $chatUpdateData = [
                'messages' => $updateData->chat,
                'context_fill' => $updateData->context_fill ?? $chat->context_fill,
            ];

            // Обновляем context только если он передан
            if ($updateData->context !== null) {
                $chat->assignContext($updateData->context);
            }

            $chat->update($chatUpdateData);

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

            // После обновления токенов и (возможно) agent_model — рассчитываем стоимость через PricingService
            $promptTokens = (int) ($agentTask->prompt_tokens ?? 0);
            $completionTokens = (int) ($agentTask->completion_tokens ?? 0);

            $calculatedCost = $this->pricingService->calculateCost($agentTask->agent_model, $promptTokens, $completionTokens);
            if ($calculatedCost !== null) {
                $agentTask->cost = $calculatedCost;
            } else {
                $agentTask->cost = null;
            }
            $agentTask->save();

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
                'exception' => $e,
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
            'type' => $task->type,
            'agent_uuid' => $task->agent_uuid,
            'project_id' => $task->project_id,
            'result_required' => $task->result_required,
            'agent_model' => $task->agent_model,
            'context_id' => $task->getContextId(),
            'chat' => $task->llmChat->toApiArray(),
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

    /**
     * Создать подзадачу для существующей задачи
     * POST /api/agent/task/{id}/subtasks
     */
    public function createSubtask(CreateSubtaskRequest $request, int $id): JsonResponse
    {
        $agent = $request->get('agent');
        $agentUuid = $request->getAgentUuid();

        try {
            // Авторизационная проверка владения родительской задачей
            $parent = AgentTask::find($id);

            if (!$parent) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Parent task not found',
                ], 404);
            }

            if (!$parent->agent_id || $parent->agent_id !== $agent->id || ($parent->agent_uuid && $parent->agent_uuid !== $agentUuid)) {
                $this->logSuspiciousActivity($request, 'subtask_create_denied', [
                    'requested_parent_task_id' => $id,
                    'agent_id' => $agent->id,
                    'agent_uuid' => $agentUuid,
                    'reason' => 'parent_task_not_owned',
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Forbidden: parent task not owned by this agent',
                ], 403);
            }

            // Создать DTO для создания подзадачи
            $dto = AgentSubtaskCreateDTO::fromArray(array_merge($request->validated(), ['parent_task_id' => $id]));

            // Создать подзадачу
            $subtaskId = $this->taskManager->createSubtask(
                $dto->parentTaskId,
                $agent->id,
                $agentUuid,
                $dto->type
            );

            return response()->json(['id' => $subtaskId], 201);

        } catch (\Exception $e) {
            Log::error('API: Failed to create subtask', [
                'parent_task_id' => $id,
                'agent_id' => $agent->id,
                'agent_uuid' => $agentUuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to create subtask'
            ], 500);
        }
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
