<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAgentRequest;
use App\Http\Requests\UpdateAgentRequest;
use App\Jobs\RegisterAgentJob;
use App\Models\Agent;
use App\Models\AgentTask;
use App\Models\Project;
use App\Services\AgentJwtService;
use App\Services\AgentNameGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AgentController extends Controller
{
    public function __construct(
        private AgentJwtService $jwtService,
        private AgentNameGenerator $nameGenerator
    ) {}

    /**
     * Список агентов проекта
     */
    public function index(Project $project): Response
    {
        if (!$project->canAccess(Auth::user())) {
            abort(403);
        }

        $agents = $project->agents()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Сводка по задачам агентов в проекте
        $statusCounts = AgentTask::query()
            ->where('project_id', $project->id)
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $totalTasks = $statusCounts->sum();
        $lastCreatedAt = AgentTask::query()
            ->where('project_id', $project->id)
            ->latest('created_at')
            ->value('created_at');

        return Inertia::render('agents/Index', [
            'project' => $project,
            'agents' => $agents,
            'taskSummary' => [
                'total' => $totalTasks,
                'waiting' => (int)($statusCounts[AgentTask::STATUS_WAIT] ?? 0),
                'processing' => (int)($statusCounts[AgentTask::STATUS_PROCESSING] ?? 0),
                'success' => (int)($statusCounts[AgentTask::STATUS_SUCCESS] ?? 0),
                'failed' => (int)($statusCounts[AgentTask::STATUS_FAILED] ?? 0),
                'lastCreatedAt' => $lastCreatedAt,
            ],
        ]);
    }

    /**
     * Форма создания агента
     */
    public function create(Project $project): Response
    {
        if (!$project->canAccess(Auth::user())) {
            abort(403);
        }

        $suggestedName = $this->nameGenerator->generate($project);

        return Inertia::render('agents/Create', [
            'project' => $project,
            'suggestedName' => $suggestedName,
        ]);
    }

    /**
     * Сохранение агента
     */
    public function store(StoreAgentRequest $request, Project $project): RedirectResponse
    {
        if (!$project->canAccess(Auth::user())) {
            abort(403);
        }

        $name = $request->validated('name');

        // Генерируем UUID для агента
        $agentUuid = Str::uuid()->toString();

        // Создаем агента с UUID
        $agent = $project->agents()->create([
            'name' => $name,
            'uuid' => $agentUuid,
            'token' => '', // Временно пустой токен
        ]);

        // Затем генерируем токен для созданного агента
        $token = $this->jwtService->generateToken($agent);
        $agent->update(['token' => $token]);

        // Запускаем фоновую задачу регистрации агента в оркестраторе
        RegisterAgentJob::dispatch($agent->id);

        // Переадресуем на страницу редактирования агента
        return redirect()
            ->route('projects.agents.edit', [$project, $agent])
            ->with('success', 'Агент успешно создан. Регистрация в оркестраторе выполняется в фоне.');
    }

    /**
     * Форма редактирования агента
     */
    public function edit(Project $project, Agent $agent): Response
    {
        if (!$project->canAccess(Auth::user())) {
            abort(403);
        }

        // Загружаем агента с токеном
        $agent->load('project');

        return Inertia::render('agents/Edit', [
            'project' => $project,
            'agent' => $agent,
        ]);
    }

    /**
     * Обновление агента
     */
    public function update(UpdateAgentRequest $request, Project $project, Agent $agent): RedirectResponse
    {
        if (!$project->canAccess(Auth::user())) {
            abort(403);
        }

        $agent->update($request->validated());

        return redirect()
            ->route('projects.agents.index', $project)
            ->with('success', 'Агент успешно обновлен');
    }

    /**
     * Удаление агента
     */
    public function destroy(Project $project, Agent $agent): RedirectResponse
    {
        if (!$project->canAccess(Auth::user())) {
            abort(403);
        }

        $agent->delete();

        return redirect()
            ->route('projects.agents.index', $project)
            ->with('success', 'Агент успешно удален');
    }

    /**
     * Регенерация токена агента
     */
    public function regenerateToken(Project $project, Agent $agent): RedirectResponse
    {
        if (!$project->canAccess(Auth::user())) {
            abort(403);
        }

        $newToken = $this->jwtService->revokeToken($agent);

        return redirect()
            ->route('projects.agents.edit', [$project, $agent])
            ->with('success', 'Токен агента успешно обновлен')
            ->with('newToken', $newToken);
    }
}
