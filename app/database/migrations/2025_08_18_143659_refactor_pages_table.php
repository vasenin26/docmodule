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
        Schema::table('pages', function (Blueprint $table) {
            // Добавляем новые поля
            $table->foreignId('version_id')->nullable()->constrained('page_versions')->onDelete('cascade');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('deleted_at')->nullable();
            
            // Добавляем индексы для новых полей
            $table->index('version_id');
            $table->index('deleted_by');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            // Сначала удаляем внешние ключи
            $table->dropForeign(['version_id']);
            $table->dropForeign(['deleted_by']);
            
            // Затем удаляем индексы
            $table->dropIndex(['version_id']);
            $table->dropIndex(['deleted_by']);
            $table->dropIndex(['deleted_at']);
            
            // Удаляем поля
            $table->dropColumn(['version_id', 'deleted_by', 'deleted_at']);
        });
    }
};
