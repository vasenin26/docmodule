<?php

namespace App\Http\Controllers;

use App\Jobs\CalculateVersionDifferenceJob;
use App\Models\Page;
use App\Models\PageDiffDescription;
use App\Models\Project;
use App\Services\TaskManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Project $project = null)
    {
        $query = Page::where('current', true)
            ->with(['creator', 'children.creator', 'project']);

        // Фильтрация по проекту (если это страницы в контексте проекта)
        if ($project) {
            // Проверяем доступ к проекту
            if ($project->owner_id !== Auth::id()) {
                abort(403);
            }
            $query->where('project_id', $project->id);
        }

        // Фильтрация по родительской странице
        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        } else {
            $query->whereNull('parent_id');
        }

        // Поиск по названию и содержимому
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $pages = $query->orderBy('created_at', 'desc')->paginate(20);

        // Добавляем информацию о черновиках для каждой страницы
        $pages->getCollection()->transform(function ($page) {
            $page->hasActiveDraft = $page->hasActiveDraft();
            return $page;
        });

        $viewName = $project ? 'pages/Index' : 'pages/Index';
        
        return Inertia::render($viewName, [
            'pages' => $pages,
            'filters' => $request->only(['search', 'parent_id']),
            'project' => $project,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request, Project $project = null)
    {
        // Проверяем доступ к проекту, если он указан
        if ($project && $project->owner_id !== Auth::id()) {
            abort(403);
        }

        $parentPage = null;
        if ($request->has('parent_id')) {
            $parentPage = Page::where('current', true)
                ->where('id', $request->parent_id)
                ->first();
        }

        // Получаем список проектов пользователя для выбора
        $projects = Project::where('owner_id', Auth::id())
            ->orderBy('title')
            ->get();

        return Inertia::render('pages/Create', [
            'parentPage' => $parentPage,
            'project' => $project,
            'projects' => $projects,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Project $project = null)
    {
        $validation = [
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'parent_id' => 'nullable|exists:pages,id',
        ];

        // Если проект не указан в URL, валидируем project_id из формы
        if (!$project) {
            $validation['project_id'] = 'nullable|exists:projects,id';
        }

        $validated = $request->validate($validation);

        // Определяем project_id
        $projectId = null;
        if ($project) {
            // Проверяем доступ к проекту
            if ($project->owner_id !== Auth::id()) {
                abort(403);
            }
            $projectId = $project->id;
        } elseif ($request->has('project_id') && $request->project_id) {
            // Проверяем доступ к проекту из формы
            $selectedProject = Project::where('id', $request->project_id)
                ->where('owner_id', Auth::id())
                ->first();
            if ($selectedProject) {
                $projectId = $selectedProject->id;
            }
        }

        $page = Page::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'created_by' => Auth::id(),
            'parent_id' => $validated['parent_id'] ?? null,
            'project_id' => $projectId,
            'base_id' => null, // Для первой версии base_id = null
            'previous_version_id' => null, // Для первой версии previous_version_id = null
            'current' => true,
        ]);

        // Определяем куда перенаправить
        if ($project) {
            return redirect()->route('projects.show', $project)
                ->with('success', 'Страница успешно создана.');
        } else {
            return redirect()->route('pages.index')
                ->with('success', 'Страница успешно создана.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $page = Page::with(['creator', 'children.creator', 'parent', 'project', 'diffDescriptions.creator'])
            ->findOrFail($id);

        // Добавляем информацию о черновике
        $page->currentDraft = $page->getCurrentDraft();

        return Inertia::render('pages/Show', [
            'page' => $page,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $page = Page::findOrFail($id);
        $currentDraft = $page->getCurrentDraft();

        return Inertia::render('pages/Edit', [
            'page' => $page,
            'currentDraft' => $currentDraft,
            'hasActiveDraft' => $currentDraft !== null,
            'errors' => (object) [],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
        ]);

        $page = Page::where('current', true)->findOrFail($id);
        $currentDraft = $page->getCurrentDraft();

        if ($currentDraft) {
            // Обновляем существующий черновик
            $currentDraft->update([
                'title' => $request->title,
                'content' => $request->content,
            ]);
            
            return redirect()->back()
                ->with('success', 'Черновик обновлен.');
        } else {
            // Создаем новый черновик
            $draft = $page->createDraft([
                'title' => $request->title,
                'content' => $request->content,
            ]);
            
            return redirect()->back()
                ->with('success', 'Черновик создан.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $page = Page::where('current', true)->findOrFail($id);

        // Удаляем все версии страницы
        $baseId = $page->base_id ?? $page->id;
        Page::where('base_id', $baseId)->delete();

        return redirect()->route('pages.index')
            ->with('success', 'Страница успешно удалена.');
    }

    /**
     * Показать версии страницы
     */
    public function versions(string $id)
    {
        $page = Page::findOrFail($id);

        // Получаем полную цепочку версий
        $versions = $page->getVersionChain();

        return Inertia::render('pages/Versions', [
            'page' => $page,
            'versions' => $versions,
        ]);
    }

    /**
     * Восстановить версию страницы
     */
    public function restore(string $id, string $versionId)
    {
        $page = Page::findOrFail($id);
        $version = Page::findOrFail($versionId);

        // Проверяем, что версия принадлежит той же цепочке
        $pageChain = $page->getVersionChain();
        $versionInChain = $pageChain->where('id', $versionId)->first();

        if (!$versionInChain) {
            return redirect()->back()->with('error', 'Версия не найдена в цепочке страницы.');
        }

        // Создаем новую версию на основе выбранной
        $newVersion = $page->createNewVersion([
            'title' => $version->title,
            'content' => $version->content,
        ]);

        return redirect()->route('pages.index')
            ->with('success', 'Версия страницы восстановлена.');
    }

    /**
     * Утвердить черновик
     */
    public function approveDraft(Request $request, string $id, TaskManagementService $taskService)
    {
        $draft = Page::findOrFail($id);
        
        // Проверяем, является ли страница черновиком
        if (!$draft->isDraft()) {
            return redirect()->back()->with('error', 'Страница не является черновиком.');
        }
        
        // Утверждаем черновик
        $draft->approveDraft();

        // Создаем задачу только если пользователь это указал
        if ($request->boolean('create_task')) {
            try {
                // Создаем PageDiffDescription синхронно и запускаем Jobs асинхронно
                $diffDescription = $taskService->createTaskForPage($draft);
                
                // Сразу перенаправляем на страницу задачи
                // Jobs будут обрабатывать контент в фоне
                return redirect()->route('tasks.show', $diffDescription->id)
                    ->with('success', 'Черновик утвержден. Задача создана и обрабатывается.');
                    
            } catch (\Exception $e) {
                return redirect()->route('pages.show', $draft->id)
                    ->with('error', 'Черновик утвержден, но не удалось создать задачу: ' . $e->getMessage());
            }
        }

        // Переадресация на страницу просмотра если задача не создавалась
        return redirect()->route('pages.show', $draft->id)
            ->with('success', 'Черновик утвержден.');
    }

    /**
     * Получить данные черновика
     */
    public function getDraft(string $id)
    {
        $page = Page::where('current', true)->findOrFail($id);
        $draft = $page->getCurrentDraft();
        
        if (!$draft) {
            return response()->json(['error' => 'Черновик не найден'], 404);
        }
        
        return response()->json($draft);
    }

    /**
     * Удалить черновик
     */
    public function deleteDraft(string $id)
    {
        $page = Page::where('current', true)->findOrFail($id);
        $draft = $page->getCurrentDraft();
        
        if (!$draft) {
            return redirect()->back()->with('error', 'Черновик не найден.');
        }
        
        $draft->delete();
        
        return redirect('/')
            ->with('success', 'Черновик удален.');
    }

    /**
     * Создать задачу для страницы
     */
    public function createTask(string $id, TaskManagementService $taskService)
    {
        $page = Page::where('current', true)->findOrFail($id);
        
        try {
            // Создаем PageDiffDescription синхронно и запускаем Jobs асинхронно
            $diffDescription = $taskService->createTaskForPage($page);
            
            // Сразу перенаправляем на страницу задачи
            // Jobs будут обрабатывать контент в фоне
            return redirect()->route('tasks.show', $diffDescription->id)
                ->with('success', 'Задача создана и обрабатывается.');
                
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
