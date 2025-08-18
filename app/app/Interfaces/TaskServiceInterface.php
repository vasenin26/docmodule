<?php

namespace App\Interfaces;

use App\Models\Page;
use App\Models\PageDiffDescription;

interface TaskServiceInterface
{
    public function createTaskForPage(Page $page, ?int $userId = null): PageDiffDescription;
    public function canCreateTaskForPage(Page $page): bool;
}
