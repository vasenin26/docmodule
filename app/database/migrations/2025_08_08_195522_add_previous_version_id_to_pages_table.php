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
            // Добавляем поле previous_version_id для связи с предыдущей версией страницы
            $table->unsignedBigInteger('previous_version_id')->nullable()->after('base_id');
            $table->foreign('previous_version_id')->references('id')->on('pages')->onDelete('cascade');
            $table->index('previous_version_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropForeign(['previous_version_id']);
            $table->dropIndex(['previous_version_id']);
            $table->dropColumn('previous_version_id');
        });
    }
};
