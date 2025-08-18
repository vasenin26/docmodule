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
use App\Interfaces\DocumentationControlInterface;
use App\Interfaces\DraftServiceInterface;
use App\Interfaces\TaskServiceInterface;
use App\Common\DTO\PageDataDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PageController extends Controller
{
    public function __construct(
        protected DocumentationControlInterface $documentationControl,
        protected DraftServiceInterface $draftService,
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
            $page->hasActiveDraft = $page->hasActiveDraft();
            $page->isActualized = $page->isActualized(); // Для черновиков
            $page->actualizationInfo = $page->getActualizationInfo(); // Информация об актуализации
            return $page;
        });

        // Получаем DTO через DocumentationControl
        $pageListDTO = $this->documentationControl->getPageListDTO($pages, $request->only(['search', 'parent_id']));

        $pageListData = $pageListDTO->toArray();

        return Inertia::render('pages/Index', [
            'pages' => $pageListData,
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
            'parent',
            'project',
            'currentVersion.previousVersion',
            'diffDescriptions.creator',
            'latestActualization.llmChat',
            'latestActualization.createdBy'
        ]);

        // Получаем DTO через DocumentationControl
        $pageDetailDTO = $this->documentationControl->getPageDetailDTO($page);

        $pageData = $pageDetailDTO->toArray();

        return Inertia::render('pages/Show', [
            'page' => $pageData,
            'version' => [
                'id' => $page->currentVersion->id,
                'title' => $page->currentVersion->title,
                'content' => $page->currentVersion->content,
                'files' => $page->currentVersion->files ?? [],
                'created_at' => $page->currentVersion->created_at->toISOString(),
                'is_current' => true,
            ],
            'previousVersion' => $page->currentVersion->previousVersion ? [
                'id' => $page->currentVersion->previousVersion->id,
                'created_at' => $page->currentVersion->previousVersion->created_at->toISOString(),
            ] : null,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Page $page)
    {
        $currentVersion = $this->documentationControl->getCurrentVersion($page);

        return Inertia::render('pages/Edit', [
            'page' => $currentVersion,
            'is_current_version' => true,
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

        $pageData = PageDataDTO::fromArray($validated);
        $draft = $this->documentationControl->createDraftFromCurrentVersion($page, $pageData);

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
     * Show the form for editing a specific version.
     */
    public function editVersion(Page $page, PageVersion $version)
    {
        // Проверяем, что версия принадлежит странице
        if ($version->page_id !== $page->id) {
            abort(404);
        }

        $pageVersionDTO = $this->documentationControl->getPageVersion($page, $version->id);

        return Inertia::render('pages/Edit', [
            'page' => $pageVersionDTO,
            'is_current_version' => $page->checkCurrentVersion($pageVersionDTO->version_id),
            'errors' => (object) [],
        ]);
    }

    /**
     * Update a specific version of the page.
     */
    public function updateVersion(UpdatePageRequest $request, Page $page, PageVersion $version)
    {
        // Проверяем, что версия принадлежит странице
        if ($version->page_id !== $page->id) {
            abort(404);
        }

        $validated = $request->validated();

        // Создаем DTO из валидированных данных
        $pageData = PageDataDTO::fromArray($validated);

        // Если это текущая версия, создаем черновик
        if ($page->version_id === $version->id) {
            $draft = $this->documentationControl->updatePageWithDraftLogic($page, $pageData);
            return redirect()->back()
                ->with('success', $page->hasActiveDraft() ? 'Черновик обновлен.' : 'Черновик создан.');
        } else {
            // Если это не текущая версия, создаем новую версию на основе этой
            $newVersion = $page->createNewVersion([
                'title' => $validated['title'],
                'content' => $validated['content'],
                'files' => $validated['files'] ?? [],
            ]);

            return redirect()->route('pages.versions.show', [$page->id, $newVersion->id])
                ->with('success', 'Новая версия создана.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePageRequest $request, Page $page)
    {
        $validated = $request->validated();

        // Создаем DTO из валидированных данных
        $pageData = PageDataDTO::fromArray($validated);

        // Если это текущая версия, создаем черновик
        if ($request->input('is_current_version', false)) {
            $draft = $this->documentationControl->createDraftFromCurrentVersion($page, $pageData);
            return redirect()->route('pages.versions.edit', [$page->id, $draft->id])
                ->with('success', 'Черновик создан.');
        }

        // Иначе обновляем существующий черновик
        $draft = $this->documentationControl->updatePageWithDraftLogic($page, $pageData);
        return redirect()->back()
            ->with('success', 'Черновик обновлен.');
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
    public function approveDraft(Request $request, PageVersion $draft)
    {
        try {
            // Получаем страницу из черновика
            $page = $draft->page;

            $result = $this->documentationControl->approveDraftWithTask($page, $request->boolean('create_task'));

            if ($result->taskCreated) {
                return redirect()->route('tasks.show', $result->taskId)
                    ->with('success', $result->message);
            }

            return redirect()->route('pages.show', $page->id)
                ->with('success', $result->message);
        } catch (\Exception $e) {
            return redirect()->route('pages.show', $draft->page_id)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Получить данные черновика
     */
    public function getDraft(Page $page)
    {
        $draft = $this->draftService->getCurrentDraft($page);

        if (!$draft) {
            return response()->json(['error' => 'Черновик не найден'], 404);
        }

        // Возвращаем DTO вместо модели
        $draftDTO = $this->documentationControl->getDraftDTO($draft);

        return response()->json($draftDTO->toArray());
    }

    /**
     * Удалить черновик
     */
    public function deleteDraft(Page $page)
    {
        if (!$this->draftService->deleteDraft($page)) {
            return redirect()->back()->with('error', 'Черновик не найден.');
        }

        return redirect('/')
            ->with('success', 'Черновик удален.');
    }

    /**
     * Создать задачу для страницы
     */
    public function createTask(Page $page)
    {
        try {
            $diffDescription = $this->taskService->createTaskForPage($page);

            return redirect()->route('tasks.show', $diffDescription->id)
                ->with('success', 'Задача создана и обрабатывается.');

        } catch (\Exception $e) {
            return redirect()->route('pages.show', $page->id)
                ->with('error', 'Не удалось создать задачу: ' . $e->getMessage());
        }
    }
}
