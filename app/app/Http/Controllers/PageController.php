<?php

namespace App\Http\Controllers;

use App\Common\DTO\Actualization\ActualizationDTO;
use App\Http\Requests\Page\ApproveVersionRequest;
use App\Http\Requests\Page\StorePageRequest;
use App\Http\Requests\Page\UpdateVersionRequest;
use App\Interfaces\PageContextServiceFactoryInterface;
use App\Jobs\CalculateVersionDifferenceJob;
use App\Models\Page;
use App\Models\PageVersion;
use App\Models\Project;
use App\Models\VersionDiffTask;
use App\Services\ActualizationService;
use App\Services\HtmlToMdConvertor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
    public function index(Request $request, ?Project $project)
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
    public function create(Request $request, Project $project = null, Page $page = null)
    {
        if ($project && $project->owner_id !== Auth::id()) {
            abort(403);
        }

        $parentPage = null;
        if ($page) {
            $parentPage = [
                'id' => $page->id,
                'title' => $page->currentVersion->title,
            ];
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

    public function store(
        StorePageRequest $request,
        Project $project,
        PageContextServiceFactoryInterface $pageContextServiceFactory,
        HtmlToMdConvertor $convertor
    )
    {
        $validated = $request->validated();

        $projectId = null;
        if ($project) {
            if (!$project->canAccess($request->user()) ) {
                abort(403);
            }
            $projectId = $project->id;
        } elseif ($request->has('project_id') && $request->project_id) {
            $selectedProject = Project::where('id', $request->project_id)
                ->where('owner_id', Auth::id())
                ->first();
            if ($selectedProject) {
                $projectId = $selectedProject->id;
            }
        }

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
            'is_important' => $validated['is_important'] ?? false,
        ]);

        $version = PageVersion::create([
            'page_id' => $page->id,
            'title' => $validated['title'],
            'content' => $convertor->toMd($validated['content'] ?? ''),
        ]);

        // Синхронизация project_files при создании
        $attachmentsInput = $validated['project_files'] ?? [];
        if (!empty($attachmentsInput)) {
            $version->syncProjectFilesByUrls($attachmentsInput, (int)$page->project_id);
        }

        $page->update(['version_id' => $version->id]);

        $pageContextServiceFactory->createForProject($project->id)->flushCache();

        return redirect()->route('pages.show', $page->id)
            ->with('success', 'Страница успешно создана.');
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
            'latestActualization.llmChat',
            'latestActualization.createdBy',
        ]);

        $displayVersionId = $page->currentVersion?->id;
        $attachedTasks = VersionDiffTask::with('creator')
            ->when($displayVersionId, function ($query) use ($displayVersionId) {
                $query->whereHas('pageVersions', function ($q) use ($displayVersionId) {
                    $q->where('page_versions.id', $displayVersionId);
                });
            })
            ->get();

        return Inertia::render('pages/Show', [
            'page' => [
                'id' => $page->id,
                'project_id' => $page->project_id,
                'title' => $page->currentVersion->title,
                'content' => $page->currentVersion->content,
                'project_files' => $page->currentVersion->projectFiles()->get(['id', 'url', 'description']),
                'hasActiveDraft' => $page->hasActiveDraft(Auth::id()),
                'currentDraft' => $page->getCurrentDraft(Auth::id()),
                'version_id' => $page->currentVersion->id,
                'created_at' => $page->currentVersion->created_at,
                'approved_at' => $page->updated_at,
                'creator' => $page->creator,
                'diffDescriptions' => $attachedTasks,
                'project' => $page->project,
                'children' => $page->children,
                'parent' => $page->parent,
                'is_important' => (bool) $page->is_important,
            ],
            'currentDraft' => $page->getCurrentDraft(Auth::id()),
            'previousVersion' => $page->currentVersion->previousVersion ? [
                'id' => $page->currentVersion->previousVersion->id,
                'created_at' => $page->currentVersion->previousVersion->created_at->toISOString(),
            ] : null,
            'project_id' => $page->project_id,
        ]);
    }

    public function edit(Page $page, HtmlToMdConvertor $convertor)
    {
        $page->load(['project', 'currentVersion.projectFiles']);

        return Inertia::render('pages/Edit', [
            'page' => $page,
            'pageVersion' => [
                'id' => $page->currentVersion->id,
                'title' => $page->currentVersion->title,
                'content' => $convertor->toHtml($page->currentVersion->content ?? ''),
                'is_draft' => $page->currentVersion->is_draft,
                'page_id' => $page->currentVersion->page_id,
                'project_files' => $page->currentVersion->projectFiles()->get(['id', 'url', 'description']),
            ],
            'is_current_version' => true,
            'errors' => (object)[],
            'project_id' => $page->project_id,
            'actualization' => null
        ]);
    }

    public function editVersion(Page $page, PageVersion $version, HtmlToMdConvertor $convertor)
    {
        if ($version->page_id !== $page->id) {
            abort(404);
        }

        $page->load(['project']);
        $version->load(['projectFiles']);

        $actualization = $version->actualisation;

        return Inertia::render('pages/Edit', [
            'pageVersion' => [
                'id' => $version->id,
                'title' => $version->title,
                'content' => $convertor->toHtml($version->content ?? ''),
                'files' => $version->files,
                'is_draft' => $version->is_draft,
                'page_id' => $version->page_id,
                'project_files' => $page->currentVersion->projectFiles()->get(['id', 'url', 'description']),
            ],
            'page' => $page,
            'is_current_version' => $page->checkCurrentVersion($version->id),
            'actualization' => $actualization ?
                [
                    ...$actualization->toArray(),
                    'generating' => $actualization->isGenerating()
                ] : null,
            'errors' => (object)[],
            'project_id' => $page->project_id,
        ]);
    }

    /**
     * Create a draft from current version.
     */
    public function createDraft(
        Request $request,
        Page $page,
        HtmlToMdConvertor $convertor,
    )
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'project_files' => 'array',
            'project_files.*.url' => 'required|string|url',
            'project_files.*.description' => 'nullable|string',
            'parent_id' => ['nullable', 'integer'],
            'is_important' => 'boolean',
        ]);

        // Validate parent existence and project membership and cycle
        if (!empty($validated['parent_id'])) {
            $parent = Page::whereNotNull('version_id')->find($validated['parent_id']);
            if (!$parent) {
                return redirect()->back()->withErrors(['parent_id' => 'Выбранная родительская страница не найдена']);
            }

            // Ensure same project
            if ($parent->project_id !== $page->project_id) {
                return redirect()->back()->withErrors(['parent_id' => 'Родительская страница должна принадлежать тому же проекту']);
            }

            // Check cycle
            if ($this->isDescendant($validated['parent_id'], $page->id)) {
                return redirect()->back()->withErrors(['parent_id' => 'Нельзя назначить дочернюю страницу родителем']);
            }
        }

        $draft = $page->createDraft([
            'title' => $validated['title'],
            'content' => $convertor->toMd($validated['content']),
        ]);

        // Сохраняем флаг на уровне страницы
        $page->update(['is_important' => $validated['is_important'] ?? $page->is_important]);

        // Обработка project_files: если переданы в форме — используем их; иначе копируем с текущей версии
        $attachmentsInput = $validated['project_files'] ?? [];
        if (!empty($attachmentsInput)) {
            $draft->syncProjectFilesByUrls($attachmentsInput, (int)$page->project_id);
        } else {
            $currentVersion = $page->currentVersion;
            if ($currentVersion) {
                $draft->copyProjectFilesFrom($currentVersion);
            }
        }

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
            'latestActualization.llmChat',
            'latestActualization.createdBy'
        ]);

        // Задачи, прикреплённые к отображаемой версии через пивот (task_page_versions)
        $attachedTasks = VersionDiffTask::with('creator')
            ->whereHas('pageVersions', function ($q) use ($version) {
                $q->where('page_versions.id', $version->id);
            })
            ->get();

        // Устанавливаем данные из конкретной версии
        $page->title = $version->title ?: 'Без названия';
        $page->content = $version->content ?: '';
        $page->version_id = $version->id;
        $page->is_current_version = $page->version_id === $version->id;

        return Inertia::render('pages/Show', [
            'page' => [
                'id' => $page->id,
                'title' => $version->title,
                'content' => $version->content,
                'project_files' => $version->projectFiles()->get(['id', 'url', 'description']),
                'hasActiveDraft' => $page->hasActiveDraft(Auth::id()),
                'currentDraft' => $page->getCurrentDraft(Auth::id()),
                'version_id' => $version->id,
                'created_at' => $version->created_at,
                'approved_at' => $page->updated_at,
                'creator' => $page->creator,
                'diffDescriptions' => $attachedTasks,
                'project' => $page->project,
                'children' => $page->children,
                'parent' => $page->parent,
                'is_important' => (bool) $page->is_important,
            ],
            'version' => [
                'id' => $version->id,
                'title' => $version->title,
                'content' => $version->content,
                'project_files' => $version->projectFiles()->get(['id', 'url', 'description']),
                'created_at' => $version->created_at->toISOString(),
                'is_current' => $page->version_id === $version->id,
            ],
            // Для обратной совместимости с фронтом, который ожидает diffDescriptions на странице
            'diffDescriptions' => $attachedTasks,
            'project_id' => $page->project_id,
        ]);
    }

    /**
     * Update a specific version of the page.
     */
    public function updateVersion(
        UpdateVersionRequest $request,
        Page $page,
        PageVersion $version,
        HtmlToMdConvertor $convertor,
        PageContextServiceFactoryInterface $pageContextServiceFactory,
    )
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
            DB::transaction(function () use ($version, $page, $validated, $attachmentsInput, $convertor) {
                $version->update([
                    'title' => $validated['title'] ?? $version->title,
                    'content' => $convertor->toMd($validated['content']),
                ]);
                if (!empty($attachmentsInput)) {
                    $version->syncProjectFilesByUrls($attachmentsInput, (int)$page->project_id);
                }
                // Обновляем флаг страницы, если он передан
                $page->update(['is_important' => $validated['is_important'] ?? $page->is_important]);
            });

            $pageContextServiceFactory->createForProject($page->project_id)->flushCache();

            return redirect()->back()
                ->with('message', 'Черновик обновлен.')
                ->with('success', true);
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
            'project_id' => $page->project_id,
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
        $newVersion->copyProjectFilesFrom($version);

        return redirect()->route('pages.index')
            ->with('success', 'Версия страницы восстановлена.');
    }

    /**
     * Утвердить черновик
     */
    public function approveDraft(ApproveVersionRequest $request, PageVersion $pageVersion, HtmlToMdConvertor $convertor)
    {
        $page = $pageVersion->page;

        if ($page->version_id === $pageVersion->id) {
            abort(401, 'Нельзя утвердить текущую версию');
        }

        $validated = $request->validated();
        $pageVersion->update([
            'title' => $validated['title'],
            'content' => $convertor->toMd($validated['content']),
        ]);

        // Обновляем флаг на уровне страницы
        $page->update(['is_important' => $request->boolean('is_important')]);

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
     * Проверить, что candidateParentId не является потомком страницы pageId
     */
    private function isDescendant(int $candidateParentId, int $pageId): bool
    {
        if ($candidateParentId === $pageId) return true;
        $parent = Page::find($candidateParentId);
        while ($parent) {
            if ($parent->id === $pageId) return true;
            $parent = $parent->parent;
        }
        return false;
    }
}
