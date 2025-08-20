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
        // Переименовываем таблицу
        Schema::rename('page_diff_descriptions', 'version_diff_tasks');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('version_diff_tasks', 'page_diff_descriptions');
    }
};
