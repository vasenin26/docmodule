<?php

namespace App\Services\ToolsService;

use App\Interfaces\GitRepoProviderInterface;
use App\Interfaces\PageContextServiceFactoryInterface;
use App\Interfaces\ToolInterface;
use App\Services\ToolsService\Tools\CurrentTime;
use App\Services\ToolsService\Tools\Git\AnalyzeClasses;
use App\Services\ToolsService\Tools\Git\AnalyzeStructure;
use App\Services\ToolsService\Tools\Git\FindConfigFiles;
use App\Services\ToolsService\Tools\Git\GetDependencies;
use App\Services\ToolsService\Tools\Git\ReadFile;
use App\Services\ToolsService\Tools\Git\SearchFileByName;
use App\Services\ToolsService\Tools\Git\ReadDir;
use App\Services\ToolsService\Tools\Git\SearchPattern;
use App\Services\ToolsService\Tools\Page\FindRelatedPages;
use App\Services\ToolsService\Tools\Page\GetActualizationInfo;
use App\Services\ToolsService\Tools\Page\GetAttachedFiles;
use App\Services\ToolsService\Tools\Page\GetHierarchyTree;
use App\Services\ToolsService\Tools\Page\GetInfo;
use App\Services\ToolsService\Tools\Page\GetProjectPages;
use App\Services\ToolsService\Tools\Page\GetTaskHistory;
use App\Services\ToolsService\Tools\SendResult;

class ToolsFactory
{
    public function __construct(
        private GitRepoProviderInterface $gitRepoProvider,
        private PageContextServiceFactoryInterface $pageContextServiceFactory,
    )
    {

    }

    public function sendResult(): ToolInterface
    {
        return new SendResult();
    }

    public function time(): ToolInterface
    {
        return new CurrentTime();
    }

    public function gitReadFile(): ToolInterface
    {
        return new ReadFile($this->gitRepoProvider);
    }

    public function gitSearchFileByName(): ToolInterface
    {
        return new SearchFileByName($this->gitRepoProvider);
    }

    public function gitReadDir(): ToolInterface
    {
        return new ReadDir($this->gitRepoProvider);
    }

    // Новые Git утилиты
    public function gitAnalyzeStructure(): ToolInterface
    {
        return new AnalyzeStructure($this->gitRepoProvider);
    }

    public function gitGetDependencies(): ToolInterface
    {
        return new GetDependencies($this->gitRepoProvider);
    }

    public function gitSearchPattern(): ToolInterface
    {
        return new SearchPattern($this->gitRepoProvider);
    }

    public function gitFindConfigFiles(): ToolInterface
    {
        return new FindConfigFiles($this->gitRepoProvider);
    }

    public function gitAnalyzeClasses(): ToolInterface
    {
        return new AnalyzeClasses($this->gitRepoProvider);
    }

    // Page утилиты (требуют projectId)
    public function pageGetInfo(int $projectId): ToolInterface
    {
        $pageContextService = $this->pageContextServiceFactory->createForProject($projectId);
        return new GetInfo($pageContextService);
    }

    public function pageGetAttachedFiles(int $projectId): ToolInterface
    {
        $pageContextService = $this->pageContextServiceFactory->createForProject($projectId);
        return new GetAttachedFiles($pageContextService, $this->gitRepoProvider);
    }

    public function pageGetProjectPages(int $projectId): ToolInterface
    {
        $pageContextService = $this->pageContextServiceFactory->createForProject($projectId);
        return new GetProjectPages($pageContextService);
    }

    public function pageGetHierarchyTree(int $projectId): ToolInterface
    {
        $pageContextService = $this->pageContextServiceFactory->createForProject($projectId);
        return new GetHierarchyTree($pageContextService);
    }

    public function pageGetRelatedPages(int $projectId): ToolInterface
    {
        $pageContextService = $this->pageContextServiceFactory->createForProject($projectId);
        return new FindRelatedPages($pageContextService);
    }

    public function pageGetActualizationInfo(int $projectId): ToolInterface
    {
        $pageContextService = $this->pageContextServiceFactory->createForProject($projectId);
        return new GetActualizationInfo($pageContextService);
    }

    public function pageGetTaskHistory(int $projectId): ToolInterface
    {
        $pageContextService = $this->pageContextServiceFactory->createForProject($projectId);
        return new GetTaskHistory($pageContextService);
    }
}
