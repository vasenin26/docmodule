<?php

namespace App\Factory;

use App\Interfaces\LLM\PromptProviderInterface;
use App\Services\PromptProvider\Interface\PromptSourceFactoryInterface;
use App\Services\PromptProvider\Interface\PromptTemplateRendererInterface;
use App\Services\PromptProvider\PromptService;
use App\Services\PromptProvider\Sources\SafePromptSource;

readonly class PromptProviderFactory
{
    public function __construct(
        private PromptTemplateRendererInterface $templateRenderer,
        private PromptSourceFactoryInterface    $sourceFactory,
    ) {}

    public function createProjectPromptService(int $projectId): PromptProviderInterface
    {
        $defaultPrompts = $this->sourceFactory->createDefaultSource();
        $projectPrompts = $this->sourceFactory->createProjectSource($projectId);
        $safeSource = new SafePromptSource($projectPrompts, $defaultPrompts);

        return new PromptService($this->templateRenderer, $safeSource);
    }

    public function createDefaultPromptService(): PromptProviderInterface
    {
        $defaultPrompts = $this->sourceFactory->createDefaultSource();
        return new PromptService($this->templateRenderer, $defaultPrompts);
    }
}
