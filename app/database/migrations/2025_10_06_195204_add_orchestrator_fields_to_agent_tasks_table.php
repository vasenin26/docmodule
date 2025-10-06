<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_tasks', function (Blueprint $table) {
            // Проверяем и добавляем поля только если их нет
            if (!Schema::hasColumn('agent_tasks', 'context_id')) {
                $table->string('context_id')->nullable()
                    ->after('agent_uuid')
                    ->comment('ID контекста для Docker volume');
            }
            
            if (!Schema::hasColumn('agent_tasks', 'timeout')) {
                $table->integer('timeout')->nullable()->default(300)
                    ->after('context_id')
                    ->comment('Таймаут выполнения в секундах');
            }
            
            if (!Schema::hasColumn('agent_tasks', 'reserved_at')) {
                $table->timestamp('reserved_at')->nullable()
                    ->after('timeout')
                    ->comment('Время резервирования задачи');
            }
            
            if (!Schema::hasColumn('agent_tasks', 'reserved_until')) {
                $table->timestamp('reserved_until')->nullable()
                    ->after('reserved_at')
                    ->comment('Время окончания резервирования');
            }
            
            if (!Schema::hasColumn('agent_tasks', 'reserved_seconds')) {
                $table->integer('reserved_seconds')->nullable()
                    ->after('reserved_until')
                    ->comment('Количество секунд резервирования');
            }
        });

        // Добавляем индексы используя raw SQL для проверки существования
        $this->addIndexIfNotExists('agent_tasks', 'context_id', 'agent_tasks_context_id_index');
        $this->addIndexIfNotExists('agent_tasks', ['status', 'reserved_until'], 'idx_status_reserved');
    }

    public function down(): void
    {
        Schema::table('agent_tasks', function (Blueprint $table) {
            // Удаляем индексы если существуют
            $this->dropIndexIfExists('agent_tasks', 'idx_status_reserved');
            $this->dropIndexIfExists('agent_tasks', 'agent_tasks_context_id_index');
            
            $columnsToRemove = ['context_id', 'timeout', 'reserved_at', 'reserved_until', 'reserved_seconds'];
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('agent_tasks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Добавить индекс если он не существует
     */
    private function addIndexIfNotExists(string $table, string|array $columns, string $indexName): void
    {
        $exists = DB::select("SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?", [$table, $indexName]);
        
        if (empty($exists)) {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($columns, $indexName) {
                $tableBlueprint->index($columns, $indexName);
            });
        }
    }

    /**
     * Удалить индекс если он существует
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        $exists = DB::select("SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?", [$table, $indexName]);
        
        if (!empty($exists)) {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($indexName) {
                $tableBlueprint->dropIndex($indexName);
            });
        }
    }
};
