<?php

namespace App\Http\Controllers\Api;

use App\Common\DTO\Page\PageApiDTO;
use App\Common\DTO\Page\PageHierarchyDTO;
use App\Common\DTO\Page\PageListDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\GetPageActualizationRequest;
use App\Http\Requests\Agent\GetPageChildrenRequest;
use App\Http\Requests\Agent\GetPageFilesRequest;
use App\Http\Requests\Agent\GetPageHierarchyRequest;
use App\Http\Requests\Agent\GetPageParentRequest;
use App\Http\Requests\Agent\GetPageRequest;
use App\Http\Requests\Agent\GetPageTasksRequest;
use App\Http\Requests\Agent\GetPageVersionRequest;
use App\Http\Requests\Agent\GetRelatedPagesRequest;
use App\Interfaces\PageContextServiceFactoryInterface;
use App\Models\Agent;
use App\Models\PageVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PageController extends Controller
{
    /**
     * Get page version by version ID
     */
    public function getPageVersion(
        GetPageVersionRequest $request,
        int $id
    ): JsonResponse {
        /** @var Agent $agent */
        $agent = $request->get('agent');

        Log::info('Page version API request', [
            'agent_id' => $agent->id,
            'project_id' => $agent->project_id,
            'version_id' => $id,
            'endpoint' => $request->path(),
            'ip' => $request->ip()
        ]);

        $version = PageVersion::with(['page'])
            ->where('id', $id)
            ->first();

        if (!$version) {
            Log::warning('Page version not found', [
                'version_id' => $id,
                'agent_id' => $agent->id,
            ]);
            return response()->json(['error' => 'Page version not found'], 404);
        }

        // Validate that the version belongs to agent's project
        if (!$version->page || $version->page->project_id !== $agent->project_id) {
            Log::warning('Access denied to page version', [
                'version_id' => $id,
                'project_id' => $agent->project_id,
                'agent_id' => $agent->id
            ]);
            return response()->json(['error' => 'Access denied to page version'], 403);
        }

        return response()->json([
            'title' => $version->title ?? '',
            'content' => $version->content ?? '',
            'pageId' => (int) $version->page_id,
            'versionId' => (int) $version->id,
            'previousVersionId' => $version->previous_version_id ? (int) $version->previous_version_id : null,
        ]);
    }
    /**
     * Get page by ID
     */
    public function getPage(
        GetPageRequest $request,
        int $id,
        PageContextServiceFactoryInterface $pageContextServiceFactory
    ): JsonResponse {
        $agent = $request->get('agent');

        Log::info('Page API request', [
            'agent_id' => $agent->id,
            'project_id' => $agent->project_id,
            'page_id' => $id,
            'endpoint' => $request->path(),
            'ip' => $request->ip()
        ]);

        // Создаем сервис с project_id агента через фабрику
        $service = $pageContextServiceFactory->createForProject($agent->project_id);

        $page = $service->getPageById($id);

        if (!$page) {
            Log::warning('Page not found', [
                'page_id' => $id,
                'project_id' => $agent->project_id,
                'agent_id' => $agent->id
            ]);
            return response()->json(['error' => 'Page not found'], 404);
        }

        $dto = PageApiDTO::fromPage($page);

        return response()->json($dto->toArray());
    }

    /**
     * Get all pages for the project
     */
    public function getPages(
        Request $request,
        PageContextServiceFactoryInterface $pageContextServiceFactory
    ): JsonResponse {
        $agent = $request->get('agent');

        Log::info('Pages list API request', [
            'agent_id' => $agent->id,
            'project_id' => $agent->project_id,
            'endpoint' => $request->path(),
            'ip' => $request->ip()
        ]);

        // Создаем сервис с project_id агента через фабрику
        $service = $pageContextServiceFactory->createForProject($agent->project_id);

        $pages = $service->getAllProjectPages();

        $dto = PageListDTO::fromCollection($pages);

        return response()->json($dto->toArray());
    }

    /**
     * Get page hierarchy
     */
    public function getPageHierarchy(
        GetPageHierarchyRequest $request,
        PageContextServiceFactoryInterface $pageContextServiceFactory
    ): JsonResponse {
        $agent = $request->get('agent');
        $rootPageId = $request->getRootPageId();

        Log::info('Page hierarchy API request', [
            'agent_id' => $agent->id,
            'project_id' => $agent->project_id,
            'root_page_id' => $rootPageId,
            'endpoint' => $request->path(),
            'ip' => $request->ip()
        ]);

        // Создаем сервис с project_id агента через фабрику
        $service = $pageContextServiceFactory->createForProject($agent->project_id);

        $pages = $service->getPageHierarchy($rootPageId);

        $hierarchy = PageHierarchyDTO::fromCollection($pages);

        return response()->json($hierarchy);
    }

    /**
     * Get page children
     */
    public function getPageChildren(
        GetPageChildrenRequest $request,
        int $id,
        PageContextServiceFactoryInterface $pageContextServiceFactory
    ): JsonResponse {
        $agent = $request->get('agent');

        Log::info('Page children API request', [
            'agent_id' => $agent->id,
            'project_id' => $agent->project_id,
            'page_id' => $id,
            'endpoint' => $request->path(),
            'ip' => $request->ip()
        ]);

        // Создаем сервис с project_id агента через фабрику
        $service = $pageContextServiceFactory->createForProject($agent->project_id);

        // Проверяем доступ к странице
        if (!$service->validatePageAccess($id)) {
            Log::warning('Access denied to page children', [
                'page_id' => $id,
                'project_id' => $agent->project_id,
                'agent_id' => $agent->id
            ]);
            return response()->json(['error' => 'Access denied to page'], 403);
        }

        $children = $service->getPageChildren($id);

        $dto = PageListDTO::fromCollection($children);

        return response()->json($dto->toArray());
    }

    /**
     * Get page parent
     */
    public function getPageParent(
        GetPageParentRequest $request,
        int $id,
        PageContextServiceFactoryInterface $pageContextServiceFactory
    ): JsonResponse {
        $agent = $request->get('agent');

        Log::info('Page parent API request', [
            'agent_id' => $agent->id,
            'project_id' => $agent->project_id,
            'page_id' => $id,
            'endpoint' => $request->path(),
            'ip' => $request->ip()
        ]);

        // Создаем сервис с project_id агента через фабрику
        $service = $pageContextServiceFactory->createForProject($agent->project_id);

        // Проверяем доступ к странице
        if (!$service->validatePageAccess($id)) {
            Log::warning('Access denied to page parent', [
                'page_id' => $id,
                'project_id' => $agent->project_id,
                'agent_id' => $agent->id
            ]);
            return response()->json(['error' => 'Access denied to page'], 403);
        }

        $parent = $service->getPageParent($id);

        if (!$parent) {
            return response()->json(null);
        }

        $dto = PageApiDTO::fromPage($parent);

        return response()->json($dto->toArray());
    }

    /**
     * Get related pages
     */
    public function getRelatedPages(
        GetRelatedPagesRequest $request,
        int $id,
        PageContextServiceFactoryInterface $pageContextServiceFactory
    ): JsonResponse {
        $agent = $request->get('agent');

        Log::info('Related pages API request', [
            'agent_id' => $agent->id,
            'project_id' => $agent->project_id,
            'page_id' => $id,
            'endpoint' => $request->path(),
            'ip' => $request->ip()
        ]);

        // Создаем сервис с project_id агента через фабрику
        $service = $pageContextServiceFactory->createForProject($agent->project_id);

        // Проверяем доступ к странице
        if (!$service->validatePageAccess($id)) {
            Log::warning('Access denied to related pages', [
                'page_id' => $id,
                'project_id' => $agent->project_id,
                'agent_id' => $agent->id
            ]);
            return response()->json(['error' => 'Access denied to page'], 403);
        }

        $relatedPages = $service->findRelatedPages($id);

        $dto = PageListDTO::fromCollection($relatedPages);

        return response()->json($dto->toArray());
    }

    /**
     * Get page with actualization
     */
    public function getPageActualization(
        GetPageActualizationRequest $request,
        int $id,
        PageContextServiceFactoryInterface $pageContextServiceFactory
    ): JsonResponse {
        $agent = $request->get('agent');

        Log::info('Page actualization API request', [
            'agent_id' => $agent->id,
            'project_id' => $agent->project_id,
            'page_id' => $id,
            'endpoint' => $request->path(),
            'ip' => $request->ip()
        ]);

        // Создаем сервис с project_id агента через фабрику
        $service = $pageContextServiceFactory->createForProject($agent->project_id);

        // Проверяем доступ к странице
        if (!$service->validatePageAccess($id)) {
            Log::warning('Access denied to page actualization', [
                'page_id' => $id,
                'project_id' => $agent->project_id,
                'agent_id' => $agent->id
            ]);
            return response()->json(['error' => 'Access denied to page'], 403);
        }

        $page = $service->getPageWithActualization($id);

        if (!$page) {
            Log::warning('Page with actualization not found', [
                'page_id' => $id,
                'project_id' => $agent->project_id,
                'agent_id' => $agent->id
            ]);
            return response()->json(['error' => 'Page not found'], 404);
        }

        $dto = PageApiDTO::fromPage($page);

        return response()->json($dto->toArray());
    }

    /**
     * Get page project files
     */
    public function getPageFiles(
        GetPageFilesRequest $request,
        int $id,
        PageContextServiceFactoryInterface $pageContextServiceFactory
    ): JsonResponse {
        $agent = $request->get('agent');

        Log::info('Page files API request', [
            'agent_id' => $agent->id,
            'project_id' => $agent->project_id,
            'page_id' => $id,
            'endpoint' => $request->path(),
            'ip' => $request->ip()
        ]);

        // Создаем сервис с project_id агента через фабрику
        $service = $pageContextServiceFactory->createForProject($agent->project_id);

        // Проверяем доступ к странице
        if (!$service->validatePageAccess($id)) {
            Log::warning('Access denied to page files', [
                'page_id' => $id,
                'project_id' => $agent->project_id,
                'agent_id' => $agent->id
            ]);
            return response()->json(['error' => 'Access denied to page'], 403);
        }

        $files = $service->getPageProjectFiles($id);

        return response()->json($files);
    }

    /**
     * Get page tasks history
     */
    public function getPageTasks(
        GetPageTasksRequest $request,
        int $id,
        PageContextServiceFactoryInterface $pageContextServiceFactory
    ): JsonResponse {
        $agent = $request->get('agent');

        Log::info('Page tasks API request', [
            'agent_id' => $agent->id,
            'project_id' => $agent->project_id,
            'page_id' => $id,
            'endpoint' => $request->path(),
            'ip' => $request->ip()
        ]);

        // Создаем сервис с project_id агента через фабрику
        $service = $pageContextServiceFactory->createForProject($agent->project_id);

        // Проверяем доступ к странице
        if (!$service->validatePageAccess($id)) {
            Log::warning('Access denied to page tasks', [
                'page_id' => $id,
                'project_id' => $agent->project_id,
                'agent_id' => $agent->id
            ]);
            return response()->json(['error' => 'Access denied to page'], 403);
        }

        $tasks = $service->getTaskHistory($id);

        $taskList = $tasks->map(function ($task) {
            return [
                'id' => $task->id,
                'status' => $task->status,
                'created_at' => $task->created_at->toISOString(),
                'updated_at' => $task->updated_at->toISOString(),
                'creator' => [
                    'id' => $task->creator->id,
                    'name' => $task->creator->name,
                    'email' => $task->creator->email,
                ],
                'techplane' => $task->techplane ? [
                    'id' => $task->techplane->id,
                    'title' => $task->techplane->title,
                ] : null,
            ];
        })->toArray();

        return response()->json($taskList);
    }
}
