<?php

namespace App\Common\Enums;

enum AgentTaskType: string
{
    case TEXT = 'text';
    case CODE = 'code';
    case TASK = 'task';
    case TECH = 'tech';
    case ACTUALIZATION = 'actualization';
    case SEARCH_RELEVANT_FILES = 'search-relevant-files';
    case TASK_PLANING = 'task-planing';
    case TERMINAL = 'terminal';

    /**
     * Получить все доступные типы задач
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Проверить, является ли переданное значение валидным типом задачи
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }

    /**
     * Получить тип задачи по значению
     */
    public static function fromValue(string $value): ?self
    {
        return self::tryFrom($value);
    }

    /**
     * Получить человекочитаемое описание типа
     */
    public function getDescription(): string
    {
        return match($this) {
            self::TEXT => 'Текстовая задача',
            self::CODE => 'Задача с кодом',
            self::TASK => 'Генерация задачи',
            self::TECH => 'Генерация техплана',
            self::ACTUALIZATION => 'Актуализация статьи документации',
            self::SEARCH_RELEVANT_FILES => 'Поиск связанных файлов',
            self::TASK_PLANING => 'Планирование работы',
            self::TERMINAL => 'Терминальная задача',
        };
    }
}
