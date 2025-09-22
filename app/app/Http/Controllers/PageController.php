<?php

namespace App\Http\Controllers;

use App\Common\DTO\ActualizationDTO;
use App\Http\Requests\Page\ApproveVersionRequest;
use App\Http\Requests\Page\StorePageRequest;
use App\Http\Requests\Page\UpdateVersionRequest;
use App\Jobs\CalculateVersionDifferenceJob;
use App\Models\ProjectFile;
use App\Models\Page;
use App\Models\PageVersion;
use App\Models\Project;
use App\Models\VersionDiffTask;
use App\Services\ActualizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PageController extends Controller
{
    public function __construct()
    {
    }

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

        // Если указан parent_id, наследуем project_id от родителя (если проект ещё не определён)
        if (!$projectId && !empty($validated['parent_id'])) {
            $parent = Page::find($validated['parent_id']);
            if ($parent) {
                $projectId = $parent->project_id;
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
        ]);

        // Синхронизация project_files при создании
        $attachmentsInput = $validated['project_files'] ?? [];
        if (!empty($attachmentsInput)) {
            $projectId = $page->project_id;
            $now = now();
            $rows = array_map(static function (array $a) use ($projectId, $now) {
                return [
                    'project_id' => $projectId,
                    'url' => $a['url'],
                    'description' => $a['description'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }, $attachmentsInput);
            if ($rows) {
                ProjectFile::upsert($rows, ['project_id', 'url'], ['description', 'updated_at']);
            }
            $urls = array_map(fn($a) => $a['url'], $attachmentsInput);
            $ids = ProjectFile::query()
                ->where('project_id', $projectId)
                ->when($urls, fn($q) => $q->whereIn('url', $urls))
                ->pluck('id')
                ->all();
            $version->projectFiles()->sync($ids);
        }

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
                'project_files' => $page->currentVersion->projectFiles()->get(['id', 'url', 'description']),
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
        $page->load(['project', 'currentVersion.projectFiles']);

        return Inertia::render('pages/Edit', [
            'page' => $page,
            'pageVersion' => $page->currentVersion,
            'is_current_version' => true,
            'errors' => (object)[],
        ]);
    }

    public function editVersion(Page $page, PageVersion $version)
    {
        if ($version->page_id !== $page->id) {
            abort(404);
        }

        $page->load(['project']);
        $version->load(['projectFiles']);

        return Inertia::render('pages/Edit', [
            'pageVersion' => $version,
            'page' => $page,
            'is_current_version' => $page->checkCurrentVersion($version->id),
            'actualization' => $version->getActiveActualization(),
            'errors' => (object)[],
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
            'project_files' => 'array',
            'project_files.*.url' => 'required|string|url',
            'project_files.*.description' => 'nullable|string',
        ]);

        $draft = $page->createDraft($validated);

        return redirect()->route('pages.versions.edit', [$page->id, $draft->id])
            ->with('success', 'Черновик создан.');
    }

    /**
     * Запустить актуализацию для страницы (создает черновик из текущей версии)
     * Используется на странице просмотра (Show.vue)
     */
    public function actualizeContent(ActualizationService $actualizationService, Request $request, Page $page): JsonResponse
    {
        // Бизнес-валидация: проверяем правила актуализации
        $validationErrors = $this->validatePageForActualization($page);
        if (!empty($validationErrors)) {
            return response()->json([
                'success' => false,
                'message' => implode(', ', $validationErrors)
            ], 422);
        }

        try {
            // Создаем черновик из текущей версии и запускаем актуализацию
            $actualization = $actualizationService->initiateForCurrentVersion($page, $request->user());
            $actualizationDTO = ActualizationDTO::fromModel($actualization);

            return response()->json([
                'success' => true,
                'message' => 'Актуализация успешно запущена. Создан черновик.',
                'data' => $actualizationDTO->toArray()
            ]);

        } catch (\RuntimeException $e) {
            Log::error('Actualization runtime error', [
                'message' => $e->getMessage(),
                'page_id' => $page->id,
                'user_id' => $request->user()->id ?? 'no user',
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Actualization error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'page_id' => $page->id,
                'user_id' => $request->user()->id ?? 'no user',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при запуске актуализации',
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
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
        $page->version_id = $version->id;
        $page->is_current_version = $page->version_id === $version->id;

        // Получаем DTO через DocumentationControl
        $pageDetailDTO = $this->documentationControl->getPageDetailDTO($page);

        return Inertia::render('pages/Show', [
            'page' => array_merge($pageDetailDTO->toArray(), [
                'project_files' => $version->projectFiles()->get(['id', 'url', 'description']),
            ]),
            'version' => [
                'id' => $version->id,
                'title' => $version->title,
                'content' => $version->content,
                'project_files' => $version->projectFiles()->get(['id', 'url', 'description']),
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
            $attachmentsInput = $validated['project_files'] ?? [];
            unset($validated['project_files']);
            \DB::transaction(function () use ($version, $page, $validated, $attachmentsInput) {
                $version->update($validated);
                $projectId = $page->project_id;
                $now = now();
                $rows = array_map(static function (array $a) use ($projectId, $now) {
                    return [
                        'project_id' => $projectId,
                        'url' => $a['url'],
                        'description' => $a['description'] ?? null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }, $attachmentsInput);
                if ($rows) {
                    ProjectFile::upsert($rows, ['project_id', 'url'], ['description', 'updated_at']);
                }
                $urls = array_map(fn($a) => $a['url'], $attachmentsInput);
                $ids = ProjectFile::query()
                    ->where('project_id', $projectId)
                    ->when($urls, fn($q) => $q->whereIn('url', $urls))
                    ->pluck('id')
                    ->all();
                $version->projectFiles()->sync($ids);
            });

            return redirect()->back()
                ->with('success', 'Черновик обновлен.');
        }
    }

    public function update(Request $request, Page $page)
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
        ]);
        // копирование связей project_files
        $ids = $version->projectFiles()->pluck('project_files.id')->all();
        $newVersion->projectFiles()->sync($ids);

        return redirect()->route('pages.index')
            ->with('success', 'Версия страницы восстановлена.');
    }

    /**
     * Утвердить черновик
     */
    public function approveDraft(ApproveVersionRequest $request, PageVersion $pageVersion)
    {
        $page = $pageVersion->page;

        if ($page->version_id === $pageVersion->id) {
            abort(401, 'Нельзя утвердить текущую версию');
        }

        $validated = $request->validated();
        $pageVersion->update($validated);

        $page->approveDraft($pageVersion);

        if ($request->boolean('createTask')) {
            $currentVersion = $page->currentVersion;
            if ($currentVersion) {

                $this->createTaskForPage($page);
            }
        }

        return redirect()->route('pages.show', $page->id)
            ->with('success');
    }

    public function createTask(Page $page)
    {
        // Получаем текущую версию страницы
        $currentVersion = $page->currentVersion;
        if (!$currentVersion) {
            throw new \Exception('У страницы нет текущей версии');
        }

        $this->createTaskForPage($page);

        return redirect()->route('pages.show', $page->id);
    }

    private function createTaskForPage(Page $page): void
    {
        // Получаем текущую версию страницы
        $currentVersion = $page->currentVersion;
        if (!$currentVersion) {
            throw new \Exception('У страницы нет текущей версии');
        }
        // Вычисляем oldVersion из цепочки версий
        $oldVersionId = $currentVersion->previous_version_id ?: null;

        // Запускаем job вычисления разницы версий согласно документации
        CalculateVersionDifferenceJob::dispatch(
            newVersionId: $currentVersion->id,
            oldVersionId: $oldVersionId
        );
    }


    /**
     * Валидация бизнес-правил для актуализации страницы
     *
     * @param Page $page
     * @return array Массив ошибок валидации
     */
    private function validatePageForActualization(Page $page): array
    {
        $errors = [];

        // Проверяем, что у страницы нет активной актуализации
        if ($page->hasActiveActualization()) {
            $errors[] = 'Page have active actualization';
        }

        if (!$page->currentVersion) {
            $errors[] = 'Page have no current version';
        } elseif ($page->currentVersion->projectFiles()->count() === 0) {
            $errors[] = 'Page have no files';
        }

        return $errors;
    }
}
