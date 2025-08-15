<?php

namespace App\Interfaces;

interface PageContextServiceFactoryInterface
{
    public function createForProject(int $projectId): PageContextServiceInterface;
}
