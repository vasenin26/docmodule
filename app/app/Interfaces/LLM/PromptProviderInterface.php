<?php

namespace App\Interfaces\LLM;

use App\Common\DTO\DifferenceDataDTO;
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
}
