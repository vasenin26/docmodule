<?php

namespace App\Common\Enums;

enum PromptType: string
{
    case TASK_MANAGER = 'task-manager';
    case TASK_DESCRIPTION = 'task-description';
    case TECHLEAD_ROLE = 'techlead-role';
    case TECHPLANE_INSTRUCTIONS = 'techplane-instructions';
    case DOCUMENTATION_SPECIALIST_ROLE = 'documentation-specialist-role';
    case ACTUALIZATION_INSTRUCTIONS = 'actualization-instructions';
    case DEVELOPER_ROLE = 'developer-role';
    case IMPLEMENTATION_INSTRUCTIONS = 'implementation-instructions';

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
            self::DOCUMENTATION_SPECIALIST_ROLE => 'Роль специалиста по документации',
            self::ACTUALIZATION_INSTRUCTIONS => 'Инструкции актуализации',
            self::DEVELOPER_ROLE => 'Роль разработчика',
            self::IMPLEMENTATION_INSTRUCTIONS => 'Инструкции реализации',
        };
    }
}
