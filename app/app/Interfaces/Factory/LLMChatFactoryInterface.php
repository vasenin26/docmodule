<?php

namespace App\Interfaces\Factory;

use App\Common\DTO\Actualization\ActualizationContextDTO;
use App\Common\DTO\GeneratorContextDTO;
use App\Interfaces\LLM\PromptProviderInterface;
use App\Models\LLMChat;
use App\Models\PageVersion;
use App\Models\VersionDiffTask;

interface LLMChatFactoryInterface
{
    public function createChatForGenerateDescription(PromptProviderInterface $promptProvider, VersionDiffTask $task): LLMChat;

    public function createChatForTechplane(PromptProviderInterface $promptProvider, int $projectId, string $taskDescription, GeneratorContextDTO $context): LLMChat;

    public function createChatForActualization(PromptProviderInterface $promptProvider, PageVersion $pageVersion, ActualizationContextDTO $context): LLMChat;

    public function createChatForImplementation(PromptProviderInterface $promptProvider, int $projectId, string $techplaneContent, GeneratorContextDTO $context): LLMChat;

    public function createChatForUpdatedTask(PromptProviderInterface $promptProvider, VersionDiffTask $task): LLMChat;
}
