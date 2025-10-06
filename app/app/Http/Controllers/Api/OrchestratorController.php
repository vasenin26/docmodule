<?php

namespace App\Http\Controllers\Api;

use App\Common\DTO\Orchestrator\OrchestratorTaskDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orchestrator\ReserveTaskRequest;
use App\Http\Requests\Orchestrator\UpdateProjectKeyRequest;
use App\Models\AgentTask;
use App\Models\Project;
use App\Services\OrchestratorTaskService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrchestratorController extends Controller
{
    public function __construct(
        private readonly OrchestratorTaskService $orchestratorService
    ) {}

    /**
     * Получить следующую доступную задачу
     * GET /api/v1/orchestrator/tasks/next
     */
    public function getNextTask(Request $request): JsonResponse
    {
        $agent = $request->get('agent');

        try {
            $task = $this->orchestratorService->getNextAvailableTask($agent);

            if (!$task) {
                Log::debug('Orchestrator: No tasks available', [
                    'agent_id' => $agent->id,
                ]);
                
                return response()->json(null, 204); // No Content
            }

            $dto = OrchestratorTaskDTO::fromAgentTask($task);

            return response()->json($dto->toArray(), 200);

        } catch (\Exception $e) {
            Log::error('Orchestrator: Failed to get next task', [
                'agent_id' => $agent->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to get next task'
            ], 500);
        }
    }

    /**
     * Зарезервировать задачу
     * POST /api/v1/orchestrator/tasks/{taskId}/reserve
     */
    public function reserveTask(
        ReserveTaskRequest $request,
        string $taskId
    ): JsonResponse {
        $agent = $request->get('agent');

        try {
            $task = AgentTask::findOrFail($taskId);

            // Проверяем конфликт резервирования
            $conflict = $this->orchestratorService->validateReservationConflict($task);
            if ($conflict) {
                return response()->json([
                    'error' => 'Task is already reserved',
                    'reserved_until' => $conflict['reserved_until'],
                    'agent_id' => $conflict['agent_id'],
                ], 409); // Conflict
            }

            // Резервируем задачу
            $this->orchestratorService->reserveTask(
                task: $task,
                agent: $agent,
                seconds: $request->getReserveSeconds(),
                agentUuid: $request->getAgentUuid()
            );

            // Обновляем модель из БД
            $task->refresh();

            return response()->json([
                'reserved_until' => $task->reserved_until->toIso8601String(),
                'agent_uuid' => $task->agent_uuid,
            ], 200);

        } catch (ModelNotFoundException $e) {
            Log::warning('Orchestrator: Task not found', [
                'task_id' => $taskId,
                'agent_id' => $agent->id,
            ]);

            return response()->json([
                'error' => 'Task not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Orchestrator: Failed to reserve task', [
                'task_id' => $taskId,
                'agent_id' => $agent->id,
                'error' => $e->getMessage(),
            ]);

            // Проверяем, не конфликт ли доступа
            if (str_contains($e->getMessage(), 'Access denied')) {
                return response()->json([
                    'error' => 'Access denied to this project'
                ], 403);
            }

            return response()->json([
                'error' => 'Failed to reserve task'
            ], 500);
        }
    }

    /**
     * Обновить SSH публичный ключ проекта
     * PUT /api/v1/orchestrator/projects/{projectId}/key
     */
    public function updateProjectKey(
        UpdateProjectKeyRequest $request,
        string $projectId
    ): JsonResponse {
        $agent = $request->get('agent');

        try {
            $project = Project::findOrFail($projectId);

            // КРИТИЧЕСКАЯ ПРОВЕРКА: только агенты с has_cross_project_access
            // могут обновлять ключи чужих проектов
            if (!$agent->hasCrossProjectAccess() && $project->id !== $agent->project_id) {
                Log::warning('Orchestrator: Access denied to update project key', [
                    'agent_id' => $agent->id,
                    'agent_project_id' => $agent->project_id,
                    'target_project_id' => $project->id,
                    'has_cross_project_access' => $agent->has_cross_project_access,
                ]);

                return response()->json([
                    'error' => 'Access denied to this project'
                ], 403);
            }

            $project->update([
                'public_key' => $request->getPublicKey(),
            ]);

            Log::info('Orchestrator: Project key updated', [
                'project_id' => $projectId,
                'agent_id' => $agent->id,
                'has_cross_project_access' => $agent->has_cross_project_access,
                'key_length' => strlen($request->getPublicKey()),
            ]);

            return response()->json([
                'message' => 'Public key updated successfully'
            ], 200);

        } catch (ModelNotFoundException $e) {
            Log::warning('Orchestrator: Project not found', [
                'project_id' => $projectId,
                'agent_id' => $agent->id,
            ]);

            return response()->json([
                'error' => 'Project not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Orchestrator: Failed to update project key', [
                'project_id' => $projectId,
                'agent_id' => $agent->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to update public key'
            ], 500);
        }
    }
}
