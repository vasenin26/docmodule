<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actualizations', function (Blueprint $table) {
            // 1. Добавляем новое поле page_version_id
            $table->foreignId('page_version_id')
                  ->nullable()
                  ->after('page_id')
                  ->constrained('page_versions')
                  ->onDelete('cascade');
            
            // 2. Добавляем индекс для нового поля
            $table->index(['page_version_id']);
        });

        // 3. Миграция данных: для существующих актуализаций нужно найти связанные черновики
        // ВНИМАНИЕ: Эта логика может потребовать корректировки в зависимости от текущих данных
        DB::statement("
            UPDATE actualizations a
            SET a.page_version_id = (
                SELECT pv.id 
                FROM page_versions pv 
                WHERE pv.page_id = a.page_id 
                AND pv.is_draft = 1 
                AND pv.created_at >= a.created_at
                ORDER BY pv.created_at ASC
                LIMIT 1
            )
            WHERE a.page_version_id IS NULL
        ");

        Schema::table('actualizations', function (Blueprint $table) {
            // 5. Делаем page_version_id обязательным
            $table->foreignId('page_version_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('actualizations', function (Blueprint $table) {
            $table->dropForeign(['page_version_id']);
            $table->dropIndex(['page_version_id']);
            $table->dropColumn('page_version_id');
        });
    }
};