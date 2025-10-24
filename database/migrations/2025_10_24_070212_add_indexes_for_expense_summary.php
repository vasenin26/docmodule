<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_tasks', function (Blueprint $table) {
            // Индекс по полю cost для оптимизации агрегации
            $table->index('cost');
            
            // Индекс по полю type для фильтрации по типам задач
            $table->index('type');
            
            // Индекс по полю updated_at для временной фильтрации
            $table->index('updated_at');
            
            // Составной индекс для оптимизации запросов с фильтрацией
            $table->index(['project_id', 'updated_at', 'cost']);
            $table->index(['project_id', 'type', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::table('agent_tasks', function (Blueprint $table) {
            $table->dropIndex(['cost']);
            $table->dropIndex(['type']);
            $table->dropIndex(['updated_at']);
            $table->dropIndex(['project_id', 'updated_at', 'cost']);
            $table->dropIndex(['project_id', 'type', 'updated_at']);
        });
    }
};
