<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Проверяем, есть ли уже поле page_version_id
        if (!Schema::hasColumn('version_diff_tasks', 'page_version_id')) {
            Schema::table('version_diff_tasks', function (Blueprint $table) {
                // Добавляем новое поле page_version_id
                $table->foreignId('page_version_id')->nullable()->after('page_id')->constrained('page_versions')->onDelete('cascade');
                
                // Добавляем индекс
                $table->index('page_version_id');
            });
        }
        
        // Миграция данных: привязываем существующие задачи к текущим версиям страниц
        DB::statement('
            UPDATE version_diff_tasks vdt
            JOIN pages p ON vdt.page_id = p.id
            SET vdt.page_version_id = p.version_id
            WHERE p.version_id IS NOT NULL
        ');
        
        // Проверяем, есть ли поле page_id для удаления
        if (Schema::hasColumn('version_diff_tasks', 'page_id')) {
            Schema::table('version_diff_tasks', function (Blueprint $table) {
                // Удаляем старое поле page_id
                $table->dropIndex('page_diff_descriptions_page_id_index');
                $table->dropColumn('page_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('version_diff_tasks', function (Blueprint $table) {
            // Возвращаем поле page_id
            $table->foreignId('page_id')->nullable()->after('id')->constrained('pages')->onDelete('cascade');
            $table->index('page_id');
        });
        
        // Миграция данных обратно
        DB::statement('
            UPDATE version_diff_tasks vdt
            JOIN page_versions pv ON vdt.page_version_id = pv.id
            SET vdt.page_id = pv.page_id
        ');
        
        Schema::table('version_diff_tasks', function (Blueprint $table) {
            $table->foreignId('page_id')->nullable(false)->change();
            
            // Удаляем новое поле
            $table->dropForeign('version_diff_tasks_page_version_id_foreign');
            $table->dropIndex(['page_version_id']);
            $table->dropColumn('page_version_id');
        });
    }
};
