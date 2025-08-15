<?php

namespace App\Interfaces\ContentGenerator;

use App\Common\DTO\LLMGenerationResult;

interface ActualizationGeneratorInterface
{
    /**
     * Актуализировать содержимое страницы на основе прикрепленных файлов
     *
     * @param string $currentContent Текущее содержимое страницы
     * @param array $attachedFiles Список прикрепленных файлов
     * @return LLMGenerationResult Результат генерации
     */
    public function actualize(string $currentContent, array $attachedFiles): LLMGenerationResult;
}
