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
        }

        // Преобразуем в формат пагинации для совместимости с PageList компонентом
        $paginatedPages = [
            'data' => $pages->values(), // values() для переиндексации после фильтрации
            'links' => [], // Пустые ссылки пагинации, так как getCurrentPages() возвращает все страницы
            'current_page' => 1,
            'per_page' => $pages->count(),
            'total' => $pages->count()
        ];

        return Inertia::render('projects/pages/Index', [
            'project' => $project,
            'pages' => $paginatedPages,
            'filters' => [
                'search' => $search,
                'parent_id' => $parentId
            ]
        ]);
    }
}
