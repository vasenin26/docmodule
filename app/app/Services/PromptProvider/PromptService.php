<?php

namespace App\Services\PromptProvider;

use App\Common\DTO\DifferenceDataDTO;
use App\Common\DTO\GeneratorContextDTO;
use App\Common\Enums\PromptType;
use App\Interfaces\LLM\PromptProviderInterface;
use App\Services\PromptProvider\Interface\PromptSourceInterface;
use App\Services\PromptProvider\Interface\PromptTemplateRendererInterface;

readonly class PromptService implements PromptProviderInterface
{
    public function __construct(
        private PromptTemplateRendererInterface $templateRenderer,
        private PromptSourceInterface           $promptSource,
    ) {}

    public function getDescriptionGeneratorRole(): string
    {
        $prompt = $this->promptSource->getPrompt(PromptType::TASK_MANAGER);
        return $prompt ?? 'Роль не определена';
    }

    public function getDescriptionGeneratorInstructions(DifferenceDataDTO $differenceData, array $repositories = [], array $attachedFiles = []): string
    {
        $prompt = $this->promptSource->getPrompt(PromptType::TASK_DESCRIPTION);

        if (!$prompt) {
            return 'Инструкции не найдены';
        }

        return $this->templateRenderer->render($prompt, [
            ...$differenceData->toArray(),
            'attached_files' => $attachedFiles,
            'repositories' => $repositories,
        ]);
    }

    public function getTechLeadRole(): string
    {
        $prompt = $this->promptSource->getPrompt(PromptType::TECHLEAD_ROLE);
        return $prompt ?? 'Роль TechLead не определена';
    }

    public function getTechplaneGeneratorInstructions(string $taskDescription, GeneratorContextDTO $context): string
    {
        $prompt = $this->promptSource->getPrompt(PromptType::TECHPLANE_INSTRUCTIONS);

        if (!$prompt) {
            return 'Инструкции для техплана не найдены';
        }

        return $this->templateRenderer->render($prompt, [
            'task_description' => $taskDescription,
            ...$context->toArray(), // Разворачиваем контекст
        ]);
    }

    public function getPrompt(PromptType $type): ?string
    {
        return $this->promptSource->getPrompt($type);
    }
}
