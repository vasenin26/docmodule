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
