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
        Schema::create('project_file_page_version', function (Blueprint $table) {
            $table->foreignId('page_version_id')->constrained('page_versions')->onDelete('cascade');
            $table->foreignId('project_file_id')->constrained('project_files')->onDelete('cascade');

            $table->primary(['page_version_id', 'project_file_id']);
            $table->index('project_file_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_file_page_version');
    }
};
