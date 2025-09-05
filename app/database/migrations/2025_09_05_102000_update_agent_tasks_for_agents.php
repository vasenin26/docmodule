<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('agent_tasks', function (Blueprint $table) {
            // Переименовать agent_id в agent_uuid
            $table->renameColumn('agent_id', 'agent_uuid');
            
            // Добавить новую колонку agent_id как foreign key
            $table->foreignId('agent_id')->nullable()->constrained('agents')->onDelete('set null');
            
            // Обновить индексы
            $table->dropIndex('idx_agent_id');
            $table->index('agent_uuid');
            $table->index('agent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_tasks', function (Blueprint $table) {
            // Удалить новые индексы
            $table->dropIndex(['agent_uuid']);
            $table->dropIndex(['agent_id']);
            
            // Удалить foreign key и колонку agent_id
            $table->dropForeign(['agent_id']);
            $table->dropColumn('agent_id');
            
            // Переименовать agent_uuid обратно в agent_id
            $table->renameColumn('agent_uuid', 'agent_id');
            
            // Восстановить старый индекс
            $table->index('agent_id', 'idx_agent_id');
        });
    }
};
