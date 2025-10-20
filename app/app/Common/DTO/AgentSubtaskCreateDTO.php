<?php

namespace App\Common\DTO;

use App\Common\Enums\AgentTaskType;

class AgentSubtaskCreateDTO
{
    public function __construct(
        public int $parentTaskId,
        public string $type,
    ) {}

    /**
     * Создать DTO из массива данных
     *
     * @param array $data Массив с данными
     * @return self
     * @throws \InvalidArgumentException Если отсутствуют обязательные поля
     */
    public static function fromArray(array $data): self
    {
        // Валидация обязательных полей
        if (!isset($data['parent_task_id']) || !is_numeric($data['parent_task_id'])) {
            throw new \InvalidArgumentException('Поле parent_task_id обязательно и должно быть числом');
        }

        if (!isset($data['type']) || !is_string($data['type'])) {
            throw new \InvalidArgumentException('Поле type обязательно и должно быть строкой');
        }

        // Валидация типа задачи - принимаем любую строку согласно требованию
        if (empty(trim($data['type']))) {
            throw new \InvalidArgumentException('Поле type не может быть пустым');
        }

        return new self(
            parentTaskId: (int) $data['parent_task_id'],
            type: trim($data['type']),
        );
    }

    /**
     * Получить тип задачи как enum (если это валидный тип)
     */
    public function getTypeAsEnum(): ?AgentTaskType
    {
        return AgentTaskType::tryFrom($this->type);
    }

    /**
     * Проверить, является ли тип валидным enum значением
     */
    public function hasValidEnumType(): bool
    {
        return $this->getTypeAsEnum() !== null;
    }

    /**
     * Преобразовать в массив для логирования или отладки
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'parent_task_id' => $this->parentTaskId,
            'type' => $this->type,
            'is_valid_enum_type' => $this->hasValidEnumType(),
        ];
    }
}
