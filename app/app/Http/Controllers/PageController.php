<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePageRequest;
use App\Http\Requests\UpdatePageRequest;
use App\Jobs\CalculateVersionDifferenceJob;
use App\Models\Page;
use App\Models\PageVersion;
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
        $query = Page::whereNotNull('version_id')
            ->with(['creator', 'children.creator', 'project', 'currentVersion']);

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
            $query->whereHas('currentVersion', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $pages = $query->orderBy('created_at', 'desc')->paginate(20);

        // Добавляем информацию о черновиках и актуализации для каждой страницы
        $pages->getCollection()->transform(function ($page) {
            $page->hasActiveDraft = $page->hasActiveDraft();
            $page->isActualized = $page->isActualized(); // Для черновиков
            $page->actualizationInfo = $page->getActualizationInfo(); // Информация об актуализации
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
            $parentPage = Page::whereNotNull('version_id')
                ->where('id', $request->parent_id)
                ->with('currentVersion')
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
    public function store(StorePageRequest $request, Project $project = null)
    {
        $validated = $request->validated();

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

        // Создаем страницу
        $page = Page::create([
            'parent_id' => $validated['parent_id'] ?? null,
            'created_by' => Auth::id(),
            'project_id' => $projectId,
        ]);

        // Создаем первую версию
        $version = PageVersion::create([
            'page_id' => $page->id,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'files' => $validated['files'] ?? [],
        ]);

        // Устанавливаем первую версию как текущую
        $page->update(['version_id' => $version->id]);

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
        $page = Page::with([
            'creator', 
            'children.creator', 
            'parent', 
            'project', 
            'currentVersion',
            'diffDescriptions.creator',
            'latestActualization.llmChat',
            'latestActualization.createdBy'
        ])->findOrFail($id);

        // Добавляем информацию о черновике
        $page->currentDraft = $page->getCurrentDraft();
        
        // Добавляем информацию об актуализации
        $page->hasActiveActualization = $page->hasActiveActualization();
        $page->isActualized = $page->isActualized();
        $page->actualizationInfo = $page->getActualizationInfo();

        return Inertia::render('pages/Show', [
            'page' => $page,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $page = Page::with('currentVersion')->findOrFail($id);
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
    public function update(UpdatePageRequest $request, string $id)
    {
        $validated = $request->validated();
        
        $page = Page::whereNotNull('version_id')->findOrFail($id);
        $currentDraft = $page->getCurrentDraft();

        if ($currentDraft) {
            // Обновляем существующий черновик
            $currentDraft->update([
                'title' => $validated['title'],
                'content' => $validated['content'],
                'files' => $validated['files'] ?? [],
            ]);
            
            return redirect()->back()
                ->with('success', 'Черновик обновлен.');
        } else {
            // Создаем новый черновик
            $draft = $page->createDraft([
                'title' => $validated['title'],
                'content' => $validated['content'],
                'files' => $validated['files'] ?? [],
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
        $page = Page::whereNotNull('version_id')->findOrFail($id);

        // Удаляем все версии страницы
        $page->versions()->delete();
        
        // Удаляем страницу
        $page->delete();

        return redirect()->route('pages.index')
            ->with('success', 'Страница успешно удалена.');
    }

    /**
     * Показать версии страницы
     */
    public function versions(string $id)
    {
        $page = Page::with('currentVersion')->findOrFail($id);

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
        $version = PageVersion::findOrFail($versionId);

        // Проверяем, что версия принадлежит странице
        if ($version->page_id !== $page->id) {
            return redirect()->back()->with('error', 'Версия не принадлежит данной странице.');
        }

        // Создаем новую версию на основе выбранной
        $newVersion = $page->createNewVersion([
            'title' => $version->title,
            'content' => $version->content,
            'files' => $version->files ?? [],
        ]);

        return redirect()->route('pages.index')
            ->with('success', 'Версия страницы восстановлена.');
    }

    /**
     * Утвердить черновик
     */
    public function approveDraft(Request $request, string $id, TaskManagementService $taskService)
    {
        $page = Page::findOrFail($id);
        $draft = $page->getCurrentDraft();
        
        if (!$draft) {
            return redirect()->back()->with('error', 'Черновик не найден.');
        }
        
        // Утверждаем черновик
        $page->approveDraft($draft);

        // Создаем задачу только если пользователь это указал
        if ($request->boolean('create_task')) {
            try {
                // Создаем PageDiffDescription синхронно и запускаем Jobs асинхронно
                $diffDescription = $taskService->createTaskForPage($page);
                
                // Сразу перенаправляем на страницу задачи
                // Jobs будут обрабатывать контент в фоне
                return redirect()->route('tasks.show', $diffDescription->id)
                    ->with('success', 'Черновик утвержден. Задача создана и обрабатывается.');
                    
            } catch (\Exception $e) {
                return redirect()->route('pages.show', $page->id)
                    ->with('error', 'Черновик утвержден, но не удалось создать задачу: ' . $e->getMessage());
            }
        }

        // Переадресация на страницу просмотра если задача не создавалась
        return redirect()->route('pages.show', $page->id)
            ->with('success', 'Черновик утвержден.');
    }

    /**
     * Получить данные черновика
     */
    public function getDraft(string $id)
    {
        $page = Page::whereNotNull('version_id')->findOrFail($id);
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
        $page = Page::whereNotNull('version_id')->findOrFail($id);
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
        $page = Page::whereNotNull('version_id')->findOrFail($id);
        
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
