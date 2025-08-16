<?php

namespace App\Services;

use App\Interfaces\PageContextServiceFactoryInterface;
use App\Interfaces\PageContextServiceInterface;

class PageContextServiceFactory implements PageContextServiceFactoryInterface
{
    public function createForProject(int $projectId): PageContextServiceInterface
    {
        return new PageContextService($projectId);
    }
}
