<?php

namespace App\Http\Controllers;

use App\Common\Enums\AgentTaskStatus;
use App\Factory\AgentResultHandlerFactory;
use App\Http\Resources\AgentTaskResource;
use App\Models\AgentTask;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AgentTaskController extends Controller
{
    public function index(Request $request, ?Project $project = null)
    {
        if ($project && !$project->canAccess(Auth::user())) {
            abort(403);
        }

        $query = AgentTask::with(['project', 'creator', 'agent'])
            ->orderBy('created_at', 'desc');

        if ($project) {
            $query->where('project_id', $project->id);
        }

        if ($request->filled('status')) {
            $status = $request->string('status');

            // Валидируем статус через enum
            if (AgentTaskStatus::isValid($status)) {
                $query->where('status', $status);
            }
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('handler', 'like', "%{$search}%")
                  ->orWhere('agent_uuid', 'like', "%{$search}%")
                  ->orWhere('agent_model', 'like', "%{$search}%")
                  ->orWhere('chat_id', 'like', "%{$search}%")
                  ->orWhere('context_id', 'like', "%{$search}%");

                // точное совпадение по id, если число
                if (is_numeric($search)) {
                    $q->orWhere('id', (int) $search);
                }

                $q->orWhereHas('project', function ($q2) use ($search) {
                    $q2->where('title', 'like', "%{$search}%");
                });

                $q->orWhereHas('creator', function ($q3) use ($search) {
                    $q3->where('name', 'like', "%{$search}%");
                });
            });
        }

        $paginated = $query->paginate(15)->withQueryString();

        $items = AgentTaskResource::collection($paginated->items())->resolve();

        $links = collect($paginated->linkCollection()->toArray())->map(function ($l) {
            return [
                'url' => $l['url'] ?? null,
                'label' => $l['label'] ?? null,
                'active' => $l['active'] ?? false,
            ];
        })->toArray();

        $tasks = [
            'data' => $items,
            'links' => $links,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ];

        // Получаем все доступные статусы с описаниями
        $availableStatuses = collect(AgentTaskStatus::cases())->map(function ($status) {
            return [
                'value' => $status->value,
                'label' => $status->getDescription(),
                'is_finished' => $status->isFinished(),
                'is_active' => $status->isActive(),
            ];
        })->toArray();

        return Inertia::render('agent-tasks/Index', [
            'tasks' => $tasks,
            'project' => $project ? ['id' => $project->id, 'title' => $project->title] : null,
            'filters' => [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
            ],
            'availableStatuses' => $availableStatuses,
        ]);
    }

    /**
     * Check tasks statuses for provided ids and always include user's active tasks
     * POST /agent-tasks/check
     */
    public function check(Request $request)
    {
        $this->validate($request, [
            'ids' => ['nullable', 'array'],
            'ids.*' => ['string'],
        ]);

        $ids = $request->input('ids', []);
        $user = $request->user();

        // Base query: tasks that belong to user or belong to user's projects
        $baseQuery = AgentTask::with(['project', 'creator', 'agent'])->where(function ($q) use ($user) {
            $q->where('created_by', $user->id)
              ->orWhereHas('project', function ($q2) use ($user) {
                  $q2->where('created_by', $user->id);
              });
        });

        $tasksByIds = collect([]);
        if (!empty($ids)) {
            $tasksByIds = (clone $baseQuery)->whereIn('id', $ids)->get();
        }

        $activeTasks = (clone $baseQuery)
            ->whereIn('status', [AgentTask::STATUS_PROCESSING, AgentTask::STATUS_WAIT])
            ->get();

        $tasks = $tasksByIds->concat($activeTasks)->unique('id')->values();

        // Return resource collection
        return AgentTaskResource::collection($tasks);
    }

    /**
     * Получить содержимое чата для задачи (web API)
     * GET /agent-tasks/{id}/chat-content
     */
    public function getChatContent(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $task = AgentTask::findOrFail($id);

            // Проверяем доступ к задаче через проект
            if ($task->project && !$task->project->canAccess(Auth::user())) {
                abort(403);
            }

            $llmChat = $task->llmChat;
            if (!$llmChat) {
                return response()->json(['error' => 'Chat not found'], 404);
            }

            return response()->json([
                'chat_id' => $llmChat->id,
                'content' => json_encode($llmChat->messages ?? [])
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Task not found'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to get chat content', [
                'task_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to get chat content'
            ], 500);
        }
    }

    /**
     * Определить целевой ресурс для задачи агента
     * GET /agent-tasks/{id}/target-resource
     */
    public function getTargetResource(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $task = AgentTask::findOrFail($id);

            // Проверяем доступ к задаче через проект
            if ($task->project && !$task->project->canAccess(Auth::user())) {
                abort(403);
            }

            // Создаем хендлер через фабрику
            $handlerFactory = app(AgentResultHandlerFactory::class);
            $handler = $handlerFactory->createTaskHandler($task);

            if (!$handler) {
                return response()->json(['error' => 'Handler not found'], 404);
            }

            // Получаем целевой ресурс от хендлера
            $targetResource = $handler->getTargetResource();

            if (!$targetResource) {
                return response()->json(['error' => 'Target resource not found'], 404);
            }

            return response()->json([
                'type' => class_basename($targetResource),
                'url' => $targetResource->viewPage(),
                'title' => class_basename($targetResource) . " #{$targetResource->id}"
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Task not found'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to get target resource', [
                'task_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to determine target resource: ' . $e->getMessage()
            ], 500);
        }
    }
}
