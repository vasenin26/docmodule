<?php

namespace App\Services\PromptProvider;

use App\Common\DTO\DifferenceDataDTO;
use App\Enums\PromptType;
use App\Interfaces\LLM\PromptProviderInterface;
use App\Services\PromptProvider\Sources\DefaultPrompts;
use App\Services\PromptProvider\Sources\ProjectPrompts;

class PromptService implements PromptProviderInterface
{
    public function __construct(
        private readonly DefaultPrompts $defaultProvider,
        private readonly PromptTemplateRendererInterface $templateRenderer,
        private readonly ?ProjectPrompts $projectPrompts = null,
    ) {}

    public function getDescriptionGeneratorRole(): string
    {
        $prompt = $this->getPrompt(PromptType::TASK_MANAGER);
        return $prompt ?? 'Роль не определена';
    }

    /**
     * Внутренний метод для получения промпта по типу
     */
    private function getPrompt(PromptType $type): ?string
    {
        // Сначала пытаемся получить промпт проекта
        if ($this->projectPrompts) {
            $prompt = $this->projectPrompts->getPrompt($type);
            
            if ($prompt) {
                return $prompt;
            }
        }
        
        // Если промпт проекта не найден, используем промпт по умолчанию
        return $this->defaultProvider->getPrompt($type);
    }

    public function getDescriptionGeneratorInstructions(DifferenceDataDTO $differenceData, array $repositories = [], array $attachedFiles = []): string
    {
        $prompt = $this->getPrompt(PromptType::TASK_DESCRIPTION);
        
        if (!$prompt) {
            return 'Инструкции не найдены';
        }
        
        // Преобразуем DTO в массив для шаблонизатора
        $variables = $differenceData->toArray();
        
        // Добавляем информацию о репозиториях
        if (!empty($repositories)) {
            $variables['repositories'] = array_map(function($repo) {
                if (is_string($repo)) {
                    return [
                        'url' => $repo,
                        'name' => basename($repo),
                        'description' => null
                    ];
                }
                
                if (is_array($repo)) {
                    return [
                        'url' => $repo['url'] ?? null,
                        'name' => $repo['name'] ?? basename($repo['url'] ?? 'unknown'),
                        'description' => $repo['description'] ?? null
                    ];
                }
                
                // Если это объект
                return [
                    'url' => $repo->url ?? null,
                    'name' => $repo->name ?? basename($repo->url ?? 'unknown'),
                    'description' => $repo->description ?? null
                ];
            }, $repositories);
        }
        
        // Добавляем информацию о прикрепленных файлах
        if (!empty($attachedFiles)) {
            $variables['attached_files'] = array_map(function($file) {
                if (is_string($file)) {
                    return [
                        'filename' => $file,
                        'file_path' => null,
                        'file_type' => null,
                        'file_size' => null
                    ];
                }
                
                if (is_array($file)) {
                    return [
                        'filename' => $file['filename'] ?? $file['name'] ?? 'unknown',
                        'file_path' => $file['file_path'] ?? $file['path'] ?? null,
                        'file_type' => $file['file_type'] ?? $file['type'] ?? null,
                        'file_size' => $file['file_size'] ?? $file['size'] ?? null
                    ];
                }
                
                // Если это объект
                return [
                    'filename' => $file->filename ?? $file->name ?? 'unknown',
                    'file_path' => $file->file_path ?? $file->path ?? null,
                    'file_type' => $file->file_type ?? $file->type ?? null,
                    'file_size' => $file->file_size ?? $file->size ?? null
                ];
            }, $attachedFiles);
        }
        
        return $this->templateRenderer->render($prompt, $variables);
    }
}
