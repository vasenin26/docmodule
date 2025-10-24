<?php

namespace App\Common\Enums;

enum AgentTaskStatus: string
{
    case WAIT = 'wait';
    case PROCESSING = 'processing';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case STOPPED = 'stopped';
    case ABANDONED = 'abandoned';

    /**
     * Получить все доступные статусы
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Проверить, является ли переданное значение валидным статусом
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }

    /**
     * Получить статус по значению
     */
    public static function fromValue(string $value): ?self
    {
        return self::tryFrom($value);
    }

    /**
     * Получить человекочитаемое описание статуса
     */
    public function getDescription(): string
    {
        return match($this) {
            self::WAIT => 'Ожидает выполнения',
            self::PROCESSING => 'Выполняется',
            self::SUCCESS => 'Успешно завершена',
            self::FAILED => 'Завершена с ошибкой',
            self::STOPPED => 'Остановлена',
            self::ABANDONED => 'Заброшена',
        };
    }

    /**
     * Проверить, является ли статус финальным (завершенным)
     */
    public function isFinished(): bool
    {
        return match($this) {
            self::SUCCESS, self::FAILED, self::STOPPED, self::ABANDONED => true,
            default => false,
        };
    }

    /**
     * Проверить, является ли статус активным (не завершенным)
     */
    public function isActive(): bool
    {
        return !$this->isFinished();
    }

    /**
     * Получить все финальные статусы
     */
    public static function getFinishedStatuses(): array
    {
        return [
            self::SUCCESS,
            self::FAILED,
            self::STOPPED,
            self::ABANDONED,
        ];
    }

    /**
     * Получить все активные статусы
     */
    public static function getActiveStatuses(): array
    {
        return [
            self::WAIT,
            self::PROCESSING,
        ];
    }
}
