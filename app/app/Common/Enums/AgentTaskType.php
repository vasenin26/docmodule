<?php

namespace App\Common\Enums;

enum AgentTaskType: string
{
    case TEXT = 'text';
    case CODE = 'code';

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
        };
    }
}