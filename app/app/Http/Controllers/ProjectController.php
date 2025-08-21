<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Project;
use App\Interfaces\PageContextServiceFactoryInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function __construct(
        protected PageContextServiceFactoryInterface $pageContextServiceFactory
    )
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $projects = Project::where('owner_id', Auth::id())
            ->with(['owner'])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('projects/Index', [
            'projects' => $projects
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('projects/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $project = Project::create([
            'title' => $validated['title'],
            'owner_id' => Auth::id(),
        ]);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Проект успешно создан');
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project): Response
    {
        if ($project->owner_id !== Auth::id()) {
            abort(403);
        }

        // PageContextService остается без изменений
        $pageContextService = $this->pageContextServiceFactory->createForProject($project->id);

        $project->load(['owner', 'repositories']);
        $pages = Page::where([
            'project_id' => $project->id,
            'parent_id' => null,
        ])->with(['creator', 'parent', 'children', 'currentVersion'])
            ->paginate();

        return Inertia::render('projects/Show', [
            'project' => $project,
            'pages' => $pages
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project): Response
    {
        // Проверяем доступ к проекту
        if ($project->owner_id !== Auth::id()) {
            abort(403);
        }

        $project->load(['repositories']);

        return Inertia::render('projects/Edit', [
            'project' => $project
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project): RedirectResponse
    {
        // Проверяем доступ к проекту
        if ($project->owner_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $project->update($validated);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Проект успешно обновлен');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project): RedirectResponse
    {
        // Проверяем доступ к проекту
        if ($project->owner_id !== Auth::id()) {
            abort(403);
        }

        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Проект успешно удален');
    }

    /**
     * API метод для получения проекта
     */
    public function apiShow(Project $project)
    {
        // Проверяем доступ пользователя к проекту
        if (!$project->canAccess(Auth::user())) {
            abort(403);
        }

        return response()->json($project);
    }
}
