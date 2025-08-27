<?php

namespace App\Factory;

use App\Interfaces\PageContextServiceFactoryInterface;
use App\Interfaces\PageContextServiceInterface;
use App\Services\PageContext\PageContextService;

class PageContextServiceFactory implements PageContextServiceFactoryInterface
{
    public function createForProject(int $projectId): PageContextServiceInterface
    {
        return new PageContextService($projectId);
    }
}
