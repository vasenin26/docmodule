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
        // Для PostgreSQL нужно изменить enum constraint
        // Сначала удаляем старое ограничение
        DB::statement("ALTER TABLE agent_tasks DROP CONSTRAINT IF EXISTS agent_tasks_status_check");
        
        // Добавляем новое ограничение с расширенным списком статусов
        $validStatuses = AgentTaskStatus::values();
        $statusList = "'" . implode("', '", $validStatuses) . "'";
        
        DB::statement("ALTER TABLE agent_tasks ADD CONSTRAINT agent_tasks_status_check CHECK (status IN ($statusList))");
        
        echo "Обновлен constraint для статусов agent_tasks:\n";
        foreach (AgentTaskStatus::cases() as $status) {
            echo "- {$status->value}: {$status->getDescription()}\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Удаляем новое ограничение
        DB::statement("ALTER TABLE agent_tasks DROP CONSTRAINT IF EXISTS agent_tasks_status_check");
        
        // Восстанавливаем старое ограничение
        DB::statement("ALTER TABLE agent_tasks ADD CONSTRAINT agent_tasks_status_check CHECK (status IN ('wait', 'processing', 'success', 'failed'))");
        
        echo "Восстановлен старый constraint для статусов agent_tasks\n";
    }
};
