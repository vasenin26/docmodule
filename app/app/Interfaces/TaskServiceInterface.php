<?php

namespace App\Interfaces;

use App\Models\Page;
use App\Models\PageVersion;
use App\Models\VersionDiffTask;

interface TaskServiceInterface
{
    public function createTaskForPage(Page $page, ?int $userId = null): VersionDiffTask;
    public function createTaskForPageVersion(PageVersion $pageVersion, ?int $userId = null): VersionDiffTask;
    public function canCreateTaskForPage(Page $page): bool;
}
