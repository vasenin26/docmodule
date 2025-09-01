<?php

namespace App\Interfaces;

use App\Models\Page;
use App\Models\PageVersion;
use App\Models\VersionDiffTask;

interface TaskServiceInterface
{
    public function createTaskForPageVersion(PageVersion $pageVersion, ?int $userId = null): VersionDiffTask;
}
