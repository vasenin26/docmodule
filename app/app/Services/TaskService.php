<?php

namespace App\Services;

use App\Interfaces\TaskServiceInterface;
use App\Models\Page;
use App\Models\PageDiffDescription;
use App\Services\TaskManagementService;

class TaskService implements TaskServiceInterface
{
    public function __construct(
        private TaskManagementService $taskManagementService
    ) {}

    public function createTaskForPage(Page $page, ?int $userId = null): PageDiffDescription
    {
        return $this->taskManagementService->createTaskForPage($page, $userId);
    }

    public function canCreateTaskForPage(Page $page): bool
    {
        return $this->taskManagementService->canCreateTaskForPage($page);
    }
}
