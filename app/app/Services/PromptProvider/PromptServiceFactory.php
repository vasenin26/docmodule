<?php

namespace App\Services\PromptProvider;

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
        return new PromptService($defaultPrompts, $this->templateRenderer, $projectPrompts);
    }

    public function createDefaultPromptService(): PromptService
    {
        $defaultPrompts = $this->sourceFactory->createDefaultSource();
        return new PromptService($defaultPrompts, $this->templateRenderer);
    }
}
