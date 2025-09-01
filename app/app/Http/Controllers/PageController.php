<?php

namespace App\Http\Controllers;

use App\Http\Requests\Page\StorePageRequest;
use App\Http\Requests\Page\UpdateVersionRequest;
use App\Interfaces\TaskServiceInterface;
use App\Models\Page;
use App\Models\PageVersion;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PageController extends Controller
{
    public function __construct(
        protected TaskServiceInterface $taskService
    ) {}
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
            $page->hasActiveDraft = $page->hasActiveDraft(Auth::id());
            $page->isActualized = $page->isActualized(); // Для черновиков
            $page->actualizationInfo = $page->getActualizationInfo(); // Информация об актуализации
            return $page;
        });

        return Inertia::render('pages/Index', [
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
    public function show(Page $page)
    {
        $page->load([
            'creator',
            'children.creator',
            'children.currentVersion',
            'parent',
            'parent.currentVersion',
            'project',
            'currentVersion.previousVersion',
            'diffDescriptions.creator',
            'latestActualization.llmChat',
            'latestActualization.createdBy',
        ]);

        return Inertia::render('pages/Show', [
            'page' => [
                'id' => $page->id,
                'title' => $page->currentVersion->title,
                'content' => $page->currentVersion->content,
                'files' => $page->currentVersion->files,
                'hasActiveDraft' => $page->hasActiveDraft(Auth::id()),
                'currentDraft' => $page->getCurrentDraft(Auth::id()),
                'version_id' => $page->currentVersion->id,
                'created_at' => $page->currentVersion->created_at,
                'approved_at' => $page->updated_at,
                'creator' => $page->creator,
                'diffDescriptions' => $page->diffDescriptions,
                'project' => $page->project,
                'children' => $page->children,
                'parent' => $page->parent
            ],
            'currentDraft' => $page->getCurrentDraft(Auth::id()),
            'previousVersion' => $page->currentVersion->previousVersion ? [
                'id' => $page->currentVersion->previousVersion->id,
                'created_at' => $page->currentVersion->previousVersion->created_at->toISOString(),
            ] : null,
        ]);
    }

    public function edit(Page $page)
    {
        $page->load(['project']);

        return Inertia::render('pages/Edit', [
            'page' => $page,
            'pageVersion' => $page->currentVersion,
            'is_current_version' => true,
            'errors' => (object) [],
        ]);
    }

    public function editVersion(Page $page, PageVersion $version)
    {
        if ($version->page_id !== $page->id) {
            abort(404);
        }

        $page->load(['project']);

        return Inertia::render('pages/Edit', [
            'pageVersion' => $version,
            'page' => $page,
            'is_current_version' => $page->checkCurrentVersion($version->id),
            'actualization' => $version->getActiveActualization(),
            'errors' => (object) [],
        ]);
    }

    /**
     * Create a draft from current version.
     */
    public function createDraft(Request $request, Page $page)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'files' => 'nullable|array',
            'files.*' => 'required|string|url',
        ]);

        $draft = $page->createDraft($validated);

        return redirect()->route('pages.versions.edit', [$page->id, $draft->id])
            ->with('success', 'Черновик создан.');
    }

    /**
     * Show a specific version of the page.
     */
    public function showVersion(Page $page, PageVersion $version)
    {
        // Проверяем, что версия принадлежит странице
        if ($version->page_id !== $page->id) {
            abort(404);
        }

        $page->load([
            'creator',
            'children.creator',
            'parent',
            'project',
            'diffDescriptions.creator',
            'latestActualization.llmChat',
            'latestActualization.createdBy'
        ]);

        // Устанавливаем данные из конкретной версии
        $page->title = $version->title ?: 'Без названия';
        $page->content = $version->content ?: '';
        $page->files = $version->files ?? [];
        $page->version_id = $version->id;
        $page->is_current_version = $page->version_id === $version->id;

        // Получаем DTO через DocumentationControl
        $pageDetailDTO = $this->documentationControl->getPageDetailDTO($page);

        return Inertia::render('pages/Show', [
            'page' => $pageDetailDTO->toArray(),
            'version' => [
                'id' => $version->id,
                'title' => $version->title,
                'content' => $version->content,
                'files' => $version->files ?? [],
                'created_at' => $version->created_at->toISOString(),
                'is_current' => $page->version_id === $version->id,
            ],
        ]);
    }

    /**
     * Update a specific version of the page.
     */
    public function updateVersion(UpdateVersionRequest $request, Page $page, PageVersion $version)
    {
        // Проверяем, что версия принадлежит странице
        if ($version->page_id !== $page->id) {
            abort(404);
        }

        $validated = $request->validated();

        if ($page->version_id === $version->id) {
            abort(401, 'Нельзя обновлять текущую версию');
        } else {
            $version->update($validated);

            return redirect()->back()
                ->with('success', 'Черновик обновлен.');
        }
    }

    public function update(UpdateVersionRequest $request, Page $page)
    {
        //этот метод остаётся чисто техническим, редактирование страницы возможно только админом
        abort(403);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Page $page)
    {
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
    public function versions(Page $page)
    {
        $page->load('currentVersion');

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
    public function restore(Page $page, string $versionId)
    {
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
    public function approveDraft(UpdateVersionRequest $request, PageVersion $draft)
    {
        $page = $draft->page;

        if ($page->version_id === $draft->id) {
            abort(401, 'Нельзя обновлять текущую версию');
        }

        $validated = $request->validated();
        $draft->update($validated);

        try {
            $page->approveDraft($draft);

            if($request->boolean('create_task'))
            {
                $currentVersion = $page->currentVersion;
                if ($currentVersion) {
                    $task = $this->taskService->createTaskForPageVersion($currentVersion);
                    return redirect()->route('tasks.show', $task->id);
                }
            }

            return redirect()->route('pages.show', $page->id)
                ->with('success');
        } catch (\Exception $e) {
            return redirect()->route('pages.show', $draft->page_id)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Создать задачу для страницы
     */
    public function createTask(Page $page)
    {
        try {
            // Получаем текущую версию страницы
            $currentVersion = $page->currentVersion;
            if (!$currentVersion) {
                throw new \Exception('У страницы нет текущей версии');
            }

            $versionDiffTask = $this->taskService->createTaskForPageVersion($currentVersion);

            return redirect()->route('tasks.show', $versionDiffTask->id)
                ->with('success', 'Задача создана и обрабатывается.');

        } catch (\Exception $e) {
            return redirect()->route('pages.show', $page->id)
                ->with('error', 'Не удалось создать задачу: ' . $e->getMessage());
        }
    }
}
