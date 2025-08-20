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
        Schema::table('techplanes', function (Blueprint $table) {
            // Удаляем старый внешний ключ
            $table->dropForeign(['task_id']);
            
            // Добавляем новый внешний ключ на version_diff_tasks
            $table->foreign('task_id')->references('id')->on('version_diff_tasks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('techplanes', function (Blueprint $table) {
            // Удаляем новый внешний ключ
            $table->dropForeign(['task_id']);
            
            // Восстанавливаем старый внешний ключ
            $table->foreign('task_id')->references('id')->on('page_diff_descriptions')->onDelete('cascade');
        });
    }
};
