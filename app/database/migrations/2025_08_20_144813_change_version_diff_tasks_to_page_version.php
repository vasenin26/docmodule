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

        Schema::table('version_diff_tasks', function (Blueprint $table) {
            $table->foreignId('page_id')->nullable(false)->change();

            // Удаляем новое поле
            $table->dropForeign('version_diff_tasks_page_version_id_foreign');
            $table->dropIndex(['page_version_id']);
            $table->dropColumn('page_version_id');
        });
    }
};
