<?php

namespace App\Services\PromptProvider;

use App\Common\DTO\DifferenceDataDTO;
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

    public function getPrompt(PromptType $type): ?string
    {
        return $this->promptSource->getPrompt($type);
    }
}
