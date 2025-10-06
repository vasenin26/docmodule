<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            if (!Schema::hasColumn('agents', 'has_cross_project_access')) {
                $table->boolean('has_cross_project_access')
                    ->default(false)
                    ->after('public_key')
                    ->comment('Флаг агента с доступом ко всем проектам');
            }
        });
        
        // Добавляем индекс если его нет
        $this->addIndexIfNotExists('agents', 'has_cross_project_access', 'agents_has_cross_project_access_index');
    }

    public function down(): void
    {
        // Удаляем индекс если существует
        $this->dropIndexIfExists('agents', 'agents_has_cross_project_access_index');
        
        Schema::table('agents', function (Blueprint $table) {
            if (Schema::hasColumn('agents', 'has_cross_project_access')) {
                $table->dropColumn('has_cross_project_access');
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
