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

            return response()->json([
                'task_id' => $task->id,
                'status' => $task->status,
                'assigned_at' => now()->toISOString(),
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
    }

    /**
     * Получить детали задачи
     * GET /api/agent/task/{id}
     */
    public function getTaskDetails(GetTaskDetailsRequest $request, int $id): JsonResponse
    {
        $agentId = $request->getAgentId();

        try {
            $task = AgentTask::with(['project', 'llmChat'])
                ->where('id', $id)
                ->where('agent_id', $agentId) // КРИТИЧНО: проверяем принадлежность
                ->where('status', AgentTask::STATUS_PROCESSING)
                ->first();

            if (!$task) {
                $this->logSuspiciousActivity($request, 'task_access_denied', [
                    'requested_task_id' => $id,
                    'reason' => 'task_not_found_or_not_owned'
                ]);

                return response()->json([
                    'error' => 'Task not found, not assigned to this agent, or not in processing state'
                ], 404);
            }

            // Проверяем, что задача не зависла
            $minutesSinceUpdate = now()->diffInMinutes($task->updated_at);
            if ($minutesSinceUpdate > 60) {
                Log::warning('Accessing potentially stuck task', [
                    'task_id' => $task->id,
                    'agent_id' => $agentId,
                    'minutes_since_update' => $minutesSinceUpdate,
                ]);
            }

            return response()->json([
                'id' => $task->id,
                'handler' => $task->handler,
                'handler_options' => $task->handler_options,
                'project_id' => $task->project_id,
                'chat_id' => $task->chat_id,
                'agent_id' => $task->agent_id,
                'status' => $task->status,
                'created_at' => $task->created_at->toISOString(),
                'updated_at' => $task->updated_at->toISOString(),
                'project' => [
                    'id' => $task->project->id,
                    'title' => $task->project->title,
                    'description' => $task->project->description,
                ],
                'chat' => [
                    'id' => $task->llmChat->id,
                    'messages' => $task->llmChat->messages ?? [],
                    'prompt_tokens' => $task->llmChat->prompt_tokens,
                    'completion_tokens' => $task->llmChat->completion_tokens,
                    'total_tokens' => $task->llmChat->total_tokens,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('API: Failed to get task details', [
                'task_id' => $id,
                'agent_id' => $agentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to retrieve task details'
            ], 500);
        }
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

            $handler = $handlerFactory->createTaskHandler($task);

            if (null !== $task->handler) {
                $handler->handleResult($updateData->result);
            }

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
