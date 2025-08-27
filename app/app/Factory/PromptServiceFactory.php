<?php

namespace App\Factory;

use App\Services\PromptProvider\Interface\PromptSourceFactoryInterface;
use App\Services\PromptProvider\Interface\PromptTemplateRendererInterface;
use App\Services\PromptProvider\PromptService;

class PromptServiceFactory
{
    public function __construct(
        private readonly PromptTemplateRendererInterface $templateRenderer,
        private readonly PromptSourceFactoryInterface $sourceFactory,
    ) {}

    public function createProjectPromptService(int $projectId): PromptService
    {
        $defaultPrompts = $this->sourceFactory->createDefaultSource();
        $projectPrompts = $this->sourceFactory->createProjectSource($projectId);

        return new PromptService($this->templateRenderer, $defaultPrompts, $projectPrompts);
    }

    public function createDefaultPromptService(): PromptService
    {
        $defaultPrompts = $this->sourceFactory->createDefaultSource();
        return new PromptService($this->templateRenderer, $defaultPrompts);
    }
}
