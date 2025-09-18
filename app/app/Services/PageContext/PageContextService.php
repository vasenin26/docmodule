<?php

namespace App\Services\PageContext;

use App\Interfaces\PageContextServiceInterface;
use App\Models\Page;
use App\Models\VersionDiffTask;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PageContextService implements PageContextServiceInterface
{
    private readonly int $projectId;

    public function __construct(
        int $projectId
    )
    {
        $this->projectId = $projectId;
        Log::info('PageContextService created for project', ['project_id' => $projectId]);
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function getPageById(int $pageId): ?Page
    {
        Log::debug('Getting page by ID', ['page_id' => $pageId, 'project_id' => $this->projectId]);
        $page = Page::where('id', $pageId)
            ->where('project_id', $this->projectId)
            ->whereNotNull('version_id')
            ->with(['creator', 'project', 'parent', 'children', 'currentVersion'])
            ->first();

        if (!$page) {
            Log::warning('Page not found or not accessible', [
                'page_id' => $pageId,
                'project_id' => $this->projectId
            ]);
        }

        return $page;
    }

    public function getCurrentPages(): Collection
    {
        Log::debug('Getting current pages for project', ['project_id' => $this->projectId]);

        return Page::where('project_id', $this->projectId)
            ->whereNotNull('version_id')
            ->with(['creator', 'parent', 'children', 'currentVersion'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getAllProjectPages(): Collection
    {
        Log::debug('Getting all project pages', ['project_id' => $this->projectId]);

        return Page::where('project_id', $this->projectId)
            ->whereNotNull('version_id')
            ->with(['creator', 'project', 'parent', 'children', 'currentVersion', 'actualizations'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getPageHierarchy(?int $rootPageId = null): Collection
    {
        Log::debug('Getting page hierarchy', [
            'root_page_id' => $rootPageId,
            'project_id' => $this->projectId
        ]);

        $query = Page::where('project_id', $this->projectId)
            ->whereNotNull('version_id')
            ->with(['creator', 'children.creator', 'children.children', 'currentVersion']);

        if ($rootPageId) {
            // Проверяем, что корневая страница принадлежит проекту
            if (!$this->validatePageAccess($rootPageId)) {
                return new Collection();
            }
            $query->where('parent_id', $rootPageId);
        } else {
            $query->whereNull('parent_id');
        }

        return $query->orderBy('title')->get();
    }

    public function getPageChildren(int $pageId): Collection
    {
        if (!$this->validatePageAccess($pageId)) {
            Log::warning('Access denied to page children', [
                'page_id' => $pageId,
                'project_id' => $this->projectId
            ]);
            return new Collection();
        }

        return Page::where('parent_id', $pageId)
            ->where('project_id', $this->projectId)
            ->whereNotNull('version_id')
            ->with(['creator', 'children', 'currentVersion'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getPageParent(int $pageId): ?Page
    {
        $page = $this->getPageById($pageId);
        if (!$page || !$page->parent_id) {
            return null;
        }

        return $this->getPageById($page->parent_id);
    }

    public function findRelatedPages(int $pageId): Collection
    {
        if (!$this->validatePageAccess($pageId)) {
            return new Collection();
        }

        Log::debug('Finding related pages', [
            'page_id' => $pageId,
            'project_id' => $this->projectId
        ]);

        $cacheKey = "page_context_{$this->projectId}_related_{$pageId}";
        $page = $this->getPageById($pageId);
        if (!$page) {
            return new Collection();
        }

        $relatedPages = new Collection();

        // Поиск страниц с общими файлами
        if (!empty($page->files)) {
            $relatedByFiles = Page::where('project_id', $this->projectId)
                ->whereNotNull('version_id')
                ->where('id', '!=', $pageId)
                ->with('currentVersion')
                ->get()
                ->filter(function ($otherPage) use ($page) {
                    if (empty($otherPage->files)) {
                        return false;
                    }

                    $commonFiles = array_intersect($page->files, $otherPage->files);
                    return !empty($commonFiles);
                });

            $relatedPages = $relatedPages->merge($relatedByFiles);
        }

        // Поиск страниц в той же иерархии
        $siblings = $this->getPageChildren($page->parent_id ?? 0);
        $relatedPages = $relatedPages->merge(
            $siblings->where('id', '!=', $pageId)
        );

        return $relatedPages->unique('id')->values();
    }

    public function getPageWithActualization(int $pageId): ?Page
    {
        if (!$this->validatePageAccess($pageId)) {
            return null;
        }

        $cacheKey = "page_context_{$this->projectId}_with_actualization_{$pageId}";
        return Page::where('id', $pageId)
            ->where('project_id', $this->projectId)
            ->whereNotNull('version_id')
            ->with([
                'creator',
                'currentVersion',
                'actualizations.llmChat',
                'actualizations.createdBy',
                'latestActualization',
                'completedActualization'
            ])
            ->first();
    }

    public function getPageFiles(int $pageId): array
    {
        return $this->getPageProjectFiles($pageId);
    }

    public function getPageProjectFiles(int $pageId): array
    {
        $page = $this->getPageById($pageId);
        if (!$page || !$page->version_id) {
            Log::warning('Page not found for project files retrieval', [
                'page_id' => $pageId,
                'project_id' => $this->projectId
            ]);
            return [];
        }

        $version = \App\Models\PageVersion::with('projectFiles')->find($page->version_id);
        if (!$version) {
            return [];
        }

        return $version->projectFiles->map(function ($a) {
            return [
                'id' => $a->id,
                'url' => $a->url,
                'description' => $a->description,
            ];
        })->all();
    }

    public function getTaskHistory(int $pageId): Collection
    {
        if (!$this->validatePageAccess($pageId)) {
            return new Collection();
        }

        Log::debug('Getting task history for page', [
            'page_id' => $pageId,
            'project_id' => $this->projectId
        ]);
        return VersionDiffTask::whereHas('pageVersion.page', function ($query) use ($pageId) {
            $query->where('id', $pageId)
                ->where('project_id', $this->projectId)
                ->whereNotNull('version_id');
        })
            ->with(['creator', 'llmChat', 'techplane', 'pageVersion'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function validatePageAccess(int $pageId): bool
    {
        return Page::where('id', $pageId)
            ->where('project_id', $this->projectId)
            ->whereNotNull('version_id')
            ->exists();
    }

    public function isPageInProject(int $pageId): bool
    {
        return $this->validatePageAccess($pageId);
    }
}
