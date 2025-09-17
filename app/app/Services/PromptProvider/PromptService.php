<?php

namespace App\Services\PromptProvider;

use App\Common\DTO\ActualizationContextDTO;
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

    public function getDocumentationSpecialistRole(): string
    {
        $prompt = $this->promptSource->getPrompt(PromptType::DOCUMENTATION_SPECIALIST_ROLE);
        return $prompt ?? 'Роль специалиста по документации не определена';
    }

    public function getActualizationInstructions(string $currentContent, ActualizationContextDTO $context): string
    {
        $prompt = $this->promptSource->getPrompt(PromptType::ACTUALIZATION_INSTRUCTIONS);

        if (!$prompt) {
            return 'Инструкции для актуализации не найдены';
        }

        return $this->templateRenderer->render($prompt, [
            'current_content' => $currentContent,
            ...$context->toArray(),
        ]);
    }

    public function getDeveloperRole(): string
    {
        $prompt = $this->promptSource->getPrompt(PromptType::DEVELOPER_ROLE);
        return $prompt ?? 'Роль разработчика не определена';
    }

    public function getImplementationInstructions(string $techplaneContent, GeneratorContextDTO $context): string
    {
        $prompt = $this->promptSource->getPrompt(PromptType::IMPLEMENTATION_INSTRUCTIONS);

        if (!$prompt) {
            return 'Инструкции для реализации не найдены';
        }

        return $this->templateRenderer->render($prompt, [
            'techplane_content' => $techplaneContent,
            ...$context->toArray(),
        ]);
    }

    public function getPageUpdateDescription(DifferenceDataDTO $diffInfo): string
    {
        if ($diffInfo->isNewPage) {
            $title = $diffInfo->newVersionTitle ?? 'Без названия';
            return "Создана новая страница: {$title}.";
        }

        $parts = [];

        if ($diffInfo->titleChanged) {
            $oldTitle = $diffInfo->previousVersionTitle ?? 'Без названия';
            $newTitle = $diffInfo->newVersionTitle ?? 'Без названия';
            $parts[] = "Изменён заголовок: ‘{$oldTitle}’ → ‘{$newTitle}’.";
        }

        if ($diffInfo->contentChanged) {
            $addedCount = count($diffInfo->addedLines);
            $removedCount = count($diffInfo->removedLines);
            $parts[] = "Обновлено содержимое (добавлено строк: {$addedCount}, удалено строк: {$removedCount}).";
        }

        if (empty($parts)) {
            $parts[] = 'Изменений не обнаружено.';
        }

        return implode(' ', $parts);
    }

    public function getPrompt(PromptType $type): ?string
    {
        return $this->promptSource->getPrompt($type);
    }
}
