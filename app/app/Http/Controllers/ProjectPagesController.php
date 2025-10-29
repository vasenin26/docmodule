<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Page;
use App\Interfaces\PageContextServiceFactoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProjectPagesController extends Controller
{
    public function __construct(
        protected PageContextServiceFactoryInterface $pageContextServiceFactory
    ) {}

    public function index(Request $request, Project $project)
    {
        // Проверяем доступ пользователя к проекту
        if (!$project->canAccess(Auth::user())) {
            abort(403);
        }

        // Используем PageContextService для загрузки страниц как в ProjectController
        $pageContextService = $this->pageContextServiceFactory->createForProject($project->id);
        $pages = $pageContextService->getCurrentPages();

        // Применяем фильтры поиска если они есть
        $search = $request->get('search');
        if ($search) {
            $pages = $pages->filter(function ($page) use ($search) {
                return str_contains(strtolower($page->currentVersion?->title ?? ''), strtolower($search)) ||
                       str_contains(strtolower($page->currentVersion?->content ?? ''), strtolower($search));
            });
        }

        // Фильтр по родительской странице
        $parentId = $request->get('parent_id');
        if ($parentId) {
            $pages = $pages->where('parent_id', $parentId);
        } else {
            $pages = $pages->whereNull('parent_id');
        }

        // Добавляем флаг canDelete для фронтенда
        $pages = $pages->map(function ($page) use ($project) {
            $user = Auth::user();
            $canDelete = false;

            // Владелец проекта или автор страницы может удалять
            if ($user && ($project->owner_id === $user->id || $page->created_by === $user->id)) {
                $canDelete = true;
            }

            // Если определена политика, попробуем авторизовать
            try {
                if (method_exists($this, 'authorize')) {
                    $this->authorize('delete', $page);
                    $canDelete = true;
                }
            } catch (\Exception $e) {
                // Игнорируем ошибки авторизации здесь, используем fallback
            }

            $page->canDelete = $canDelete;
            return $page;
        });

        // Преобразуем в формат пагинации для совместимости с PageList компонентом
        $paginatedPages = [
            'data' => $pages->values(), // values() для переиндексации после фильтрации
            'links' => [], // Пустые ссылки пагинации, так как getCurrentPages() возвращает все страницы
            'current_page' => 1,
            'per_page' => $pages->count(),
            'total' => $pages->count(),
        ];

        return Inertia::render('projects/pages/Index', [
            'project' => $project,
            'pages' => $paginatedPages,
            'filters' => [
                'search' => $search,
                'parent_id' => $parentId,
            ],
        ]);
    }

    /**
     * Soft-delete page within project context
     */
    public function destroy(Project $project, Page $page)
    {
        // Проверяем доступ к проекту
        if (!$project->canAccess(Auth::user())) {
            abort(403);
        }

        // Проверяем, что страница принадлежит проекту
        if ($page->project_id !== $project->id) {
            abort(404);
        }

        // Проверка прав: владелец проекта или автор страницы либо политика
        $user = Auth::user();
        if (!($user && ($project->owner_id === $user->id || $page->created_by === $user->id))) {
            // Попробуем через политику
            try {
                $this->authorize('delete', $page);
            } catch (\Exception $e) {
                abort(403);
            }
        }

        // Устанавливаем deleted_by и выполняем soft-delete. НЕ удаляем версии
        $page->update(['deleted_by' => $user?->id]);
        $page->delete();

        // Возвращаем JSON для AJAX или редирект
        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Страница мягко удалена.']);
        }

        return redirect()->route('projects.pages.index', $project)
            ->with('success', 'Страница успешно удалена.');
    }
}
