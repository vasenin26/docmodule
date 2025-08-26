<?php

namespace App\Enums;

enum PromptType: string
{
    case TASK_MANAGER = 'task-manager';
    case TASK_DESCRIPTION = 'task-description';

    public function getFileName(): string
    {
        return $this->value . '.md';
    }

    public function getLabel(): string
    {
        return match($this) {
            self::TASK_MANAGER => 'Роль агента',
            self::TASK_DESCRIPTION => 'Инструкции генерации',
        };
    }
}
