<?php

namespace App\Interfaces\LLM;

use App\Common\DTO\ActualizationContextDTO;
use App\Common\DTO\DifferenceDataDTO;
use App\Common\DTO\GeneratorContextDTO;
use App\Models\Repository;

/**
 * Интерфейс для работы с системными и пользовательскими промптами.
 *
 * Системные промпты определяют роль и поведение LLM агентов.
 * Пользовательские промпты содержат инструкции для конкретных задач.
 */
interface PromptProviderInterface
{
    /**
     * Системный промпт: определяет роль агента для генерации описания задач
     */
    public function getDescriptionGeneratorRole(): string;

    /**
     * Пользовательский промпт: возвращает инструкции для генерации описания задачи
     * с подстановкой переменных из DifferenceDataDTO, репозиториев и прикрепленных файлов
     *
     * @param DifferenceDataDTO $differenceData
     * @param Repository[] $repositories
     * @param string[] $attachedFiles
     */
    public function getDescriptionGeneratorInstructions(DifferenceDataDTO $differenceData, array $repositories = [], array $attachedFiles = []): string;

    /**
     * Системный промпт: определяет роль TechLead для генерации техпланов
     */
    public function getTechLeadRole(): string;

    /**
     * Пользовательский промпт: возвращает инструкции для генерации техплана
     * с подстановкой переменных из описания задачи и контекста
     *
     * @param string $taskDescription Описание задачи для которой генерируется техплан
     * @param GeneratorContextDTO $context Контекст генерации (файлы, репозитории и т.д.)
     */
    public function getTechplaneGeneratorInstructions(string $taskDescription, GeneratorContextDTO $context): string;

    /**
     * Системный промпт: определяет роль специалиста по документации для актуализации
     */
    public function getDocumentationSpecialistRole(): string;

    /**
     * Пользовательский промпт: возвращает инструкции для актуализации документации
     * с подстановкой переменных из текущего содержимого и контекста
     *
     * @param string $currentContent Текущее содержимое документации
     * @param ActualizationContextDTO $context Контекст актуализации (файлы, репозитории и т.д.)
     */
    public function getActualizationInstructions(string $currentContent, ActualizationContextDTO $context): string;
}
