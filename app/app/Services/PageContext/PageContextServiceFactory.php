<?php

namespace App\Services\PageContext;

use App\Interfaces\PageContextServiceFactoryInterface;
use App\Interfaces\PageContextServiceInterface;

class PageContextServiceFactory implements PageContextServiceFactoryInterface
{
    public function createForProject(int $projectId): PageContextServiceInterface
    {
        return new PageContextService($projectId);
    }
}
