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
     *
     * Note: This endpoint always returns JSON. It's used by frontend components via AJAX.
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

        // Поддержка поиска внутри указанного проекта через query param (используется PageSelect)
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // Поддержка получения конкретной страницы по id (используется для инициализации выбранного элемента в PageSelect)
        if ($request->filled('id')) {
            $query->where('id', $request->id);
        }

        // Фильтрация по родительской странице (если передан)
        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        // Поиск по названию и содержимому
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('currentVersion', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Поддержка per_page для ограничений результата при поиске (PageSelect передаёт per_page)
        $perPage = (int) $request->get('per_page', 20);

        // Всегда возвращаем JSON (это endpoint для AJAX). Формат: { data: [...], meta: { total, per_page, current_page, last_page } }
        $paginator = $query->orderBy('id', 'desc')->paginate($perPage)->appends($request->query());

        // Трансформация коллекции для возврата простого формата, используемого PageSelect
        $paginator->getCollection()->transform(function ($page) {
            return [
                'id' => (int)$page->id,
                'title' => (string)($page->currentVersion?->title ?? ''),
                'hasActiveDraft' => $page->hasActiveDraft(Auth::id()),
                'isActualized' => $page->isActualized(),
                'actualizationInfo' => $page->getActualizationInfo(),
                'parent_id' => $page->parent_id ?? null,
            ];
        });

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Simplified search for PageSelect component within a project scope.
     * Returns an array of simple objects: { id, title, path }
     */
    public function search(Request $request, Project $project): JsonResponse
    {
        // Проверяем доступ к проекту
        if ($project->owner_id !== Auth::id()) {
            abort(403);
        }

        $query = Page::whereNotNull('version_id')
            ->where('project_id', $project->id)
            ->with(['currentVersion']);

        // Если указан id — возвращаем конкретную страницу
        if ($request->filled('id')) {
            $query->where('id', $request->get('id'));
        }

        // Фильтрация по родительской странице (если передан)
        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->get('parent_id'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->whereHas('currentVersion', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $perPage = min((int)$request->get('per_page', 10), 100);

        $items = $query->orderBy('id', 'desc')->limit($perPage)->get()->map(function ($page) {
            return [
                'id' => (int)$page->id,
                'title' => (string)($page->currentVersion?->title ?? ''),
            ];
        });

        return response()->json(['data' => $items]);
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

    /** rest of file unchanged **/
