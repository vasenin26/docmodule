<?php

namespace App\Services\PromptProvider;

use App\Common\DTO\DifferenceDataDTO;
use App\Common\Enums\PromptType;
use App\Interfaces\LLM\PromptProviderInterface;
use App\Services\PromptProvider\Interface\PromptSourceInterface;
use App\Services\PromptProvider\Interface\PromptTemplateRendererInterface;

class PromptService implements PromptProviderInterface
{
    public function __construct(
        private readonly PromptTemplateRendererInterface $templateRenderer,
        private readonly PromptSourceInterface $defaultProvider,
        private readonly ?PromptSourceInterface $projectPrompts = null,
    ) {}

    private function getPrompt(PromptType $type): ?string
    {
        if ($this->projectPrompts) {
            $prompt = $this->projectPrompts->getPrompt($type);

            if ($prompt) {
                return $prompt;
            }
        }

        return $this->defaultProvider->getPrompt($type);
    }

    public function getDescriptionGeneratorRole(): string
    {
        $prompt = $this->getPrompt(PromptType::TASK_MANAGER);
        return $prompt ?? 'Роль не определена';
    }

    public function getDescriptionGeneratorInstructions(DifferenceDataDTO $differenceData, array $repositories = [], array $attachedFiles = []): string
    {
        $prompt = $this->getPrompt(PromptType::TASK_DESCRIPTION);

        if (!$prompt) {
            return 'Инструкции не найдены';
        }

        return $this->templateRenderer->render($prompt, [
            ...$differenceData->toArray(),
            'attached_files' => $attachedFiles,
            'repositories' => $repositories,
        ]);
    }
}
