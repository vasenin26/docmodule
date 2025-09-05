<?php

namespace App\Common\Enums;

enum PromptType: string
{
    case TASK_MANAGER = 'task-manager';
    case TASK_DESCRIPTION = 'task-description';
    case TECHLEAD_ROLE = 'techlead-role';
    case TECHPLANE_INSTRUCTIONS = 'techplane-instructions';

    public function getFileName(): string
    {
        return $this->value . '.md';
    }

    public function getLabel(): string
    {
        return match($this) {
            self::TASK_MANAGER => 'Роль агента',
            self::TASK_DESCRIPTION => 'Инструкции генерации',
            self::TECHLEAD_ROLE => 'Роль TechLead',
            self::TECHPLANE_INSTRUCTIONS => 'Инструкции техплана',
        };
    }
}
