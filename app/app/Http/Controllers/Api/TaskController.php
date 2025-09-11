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

        return response()->json([
            'id' => $task->id,
            'agent_uuid' => $task->agent_uuid, // Возвращаем UUID для внешнего агента
            'project_id' => $task->project_id,
            'chat' => [
                'messages' => $task->llmChat->messages ?? [],
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
            $task = AgentTask::where('id', $id)
                ->where('agent_uuid', $request->getAgentUuid()) // Проверяем по UUID от клиента
                ->where('agent_id', $agent->id) // Дополнительная проверка принадлежности агенту
                ->where('status', AgentTask::STATUS_PROCESSING)
                ->first();

            if (!$task) {
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

            $updateData = AgentTaskUpdateDTO::fromArray([
                'chat' => $request->getChatMessages(),
                'stats' => $request->getTokenStats(),
                'result' => $request->getResult(),
            ]);

            $chat = $task->llmChat;
            $chat->update([
                'messages' => $updateData->chat,
                'prompt_tokens' => ($chat->prompt_tokens ?? 0) + ($updateData->stats->prompt_tokens ?? 0),
                'completion_tokens' => ($chat->completion_tokens ?? 0) + ($updateData->stats->completion_tokens ?? 0),
                'total_tokens' => ($chat->total_tokens ?? 0) + ($updateData->stats->total_tokens ?? 0),
            ]);

            $task->update(['status' => AgentTask::STATUS_SUCCESS]);

            if ($updateData->result) {
                $handlerFactory->createTaskHandler($task)?->handleResult($updateData->result);
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
