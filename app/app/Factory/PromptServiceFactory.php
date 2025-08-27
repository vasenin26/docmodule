<?php

namespace App\Factory;

use App\Services\PromptProvider\Interface\PromptSourceFactoryInterface;
use App\Services\PromptProvider\Interface\PromptTemplateRendererInterface;
use App\Services\PromptProvider\PromptService;
use App\Services\PromptProvider\Sources\SafePromptSource;

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
        $safeSource = new SafePromptSource($projectPrompts, $defaultPrompts);

        return new PromptService($this->templateRenderer, $safeSource);
    }

    public function createDefaultPromptService(): PromptService
    {
        $defaultPrompts = $this->sourceFactory->createDefaultSource();
        return new PromptService($this->templateRenderer, $defaultPrompts);
    }
}
