<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAgentRequest;
use App\Http\Requests\UpdateAgentRequest;
use App\Models\Agent;
use App\Models\Project;
use App\Services\AgentJwtService;
use App\Services\AgentNameGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        return Inertia::render('agents/Index', [
            'project' => $project,
            'agents' => $agents,
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

        // Сначала создаем агента без токена
        $agent = $project->agents()->create([
            'name' => $name,
            'token' => '', // Временно пустой токен
        ]);

        // Затем генерируем токен для созданного агента
        $token = $this->jwtService->generateToken($agent);
        $agent->update(['token' => $token]);

        return redirect()
            ->route('projects.agents.index', $project)
            ->with('success', 'Агент успешно создан');
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
