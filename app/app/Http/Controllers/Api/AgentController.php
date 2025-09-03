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

class AgentController extends Controller
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
        $agentId = $request->getAgentId();

        try {
            $task = $this->taskManager->assignTaskToAgent($agentId);

            if (!$task) {
                return response()->json([
                    'task_id' => null,
                    'message' => 'No tasks available'
                ], 404);
            }

            Log::info('Task assigned via API', [
                'task_id' => $task->id,
                'agent_id' => $agentId,
                'handler' => $task->handler,
            ]);

        } catch (\Exception $e) {
            Log::error('API: Failed to assign task to agent', [
                'agent_id' => $agentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Service temporarily unavailable',
                'message' => 'Please try again later'
            ], 503);
        }

        return response()->json([
            'id' => $task->id,
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
        $agentId = $request->getAgentId();

        try {
            $task = AgentTask::where('id', $id)
                ->where('agent_id', $agentId) // КРИТИЧНО: проверяем принадлежность
                ->where('status', AgentTask::STATUS_PROCESSING)
                ->first();

            if (!$task) {
                $this->logSuspiciousActivity($request, 'task_update_denied', [
                    'requested_task_id' => $id,
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

            $task->llmChat->update([
                'messages' => $updateData->chat,
                'prompt_tokens' => ($chat->prompt_tokens ?? 0) + ($updateData->stats->prompt_tokens ?? 0),
                'completion_tokens' => ($chat->completion_tokens ?? 0) + ($updateData->stats->completion_tokens ?? 0),
                'total_tokens' => ($chat->total_tokens ?? 0) + ($updateData->stats->total_tokens ?? 0),
            ]);

            $task->update(['status' => AgentTask::STATUS_SUCCESS]);

            $handlerFactory->createTaskHandler($task)?->handleResult($updateData->result);

            return response()->json([
                'status' => 'updated',
                'message' => 'Task progress saved'
            ]);

        } catch (\Exception $e) {
            Log::error('API: Failed to update task', [
                'task_id' => $id,
                'agent_id' => $agentId,
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
            'agent_id' => $request->input('agent_id'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'timestamp' => now()->toISOString(),
        ], $context));
    }
}
