<?php

namespace App\Services;

use App\Interfaces\TaskServiceInterface;
use App\Models\Page;
use App\Models\PageVersion;
use App\Models\VersionDiffTask;
use App\Services\TaskManagementService;

class TaskService implements TaskServiceInterface
{
    public function __construct(
        private TaskManagementService $taskManagementService
    ) {}

    public function createTaskForPage(Page $page, ?int $userId = null): VersionDiffTask
    {
        $currentVersion = $page->currentVersion;
        if (!$currentVersion) {
            throw new \Exception('У страницы нет текущей версии');
        }
        
        return $this->createTaskForPageVersion($currentVersion, $userId);
    }

    public function createTaskForPageVersion(PageVersion $pageVersion, ?int $userId = null): VersionDiffTask
    {
        return $this->taskManagementService->createTaskForPageVersion($pageVersion, $userId);
    }

    public function canCreateTaskForPage(Page $page): bool
    {
        return $this->taskManagementService->canCreateTaskForPage($page);
    }
}
