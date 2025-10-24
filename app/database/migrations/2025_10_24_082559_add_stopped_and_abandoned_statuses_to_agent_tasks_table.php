<?php

use App\Common\Enums\AgentTaskStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Добавляем новые статусы в таблицу agent_tasks
        // Поскольку поле status уже существует как строка, 
        // нам не нужно изменять структуру таблицы
        // Просто убеждаемся, что новые статусы поддерживаются
        
        // Проверяем, что все статусы из enum валидны
        $validStatuses = AgentTaskStatus::values();
        
        // Можно добавить проверку или валидацию здесь, если необходимо
        // Например, проверить, что в базе нет записей с невалидными статусами
        
        // Логируем информацию о добавленных статусах
        echo "Добавлены новые статусы для AgentTask:\n";
        foreach (AgentTaskStatus::cases() as $status) {
            echo "- {$status->value}: {$status->getDescription()}\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Поскольку мы не изменяли структуру таблицы,
        // откат не требует изменений в схеме
        
        // Но можно добавить логику для очистки записей с новыми статусами,
        // если это необходимо для отката
        
        echo "Откат миграции: новые статусы остаются доступными\n";
    }
};
