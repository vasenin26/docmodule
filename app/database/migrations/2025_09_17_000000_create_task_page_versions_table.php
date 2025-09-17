<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('task_page_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('version_diff_tasks')->onDelete('cascade');
            $table->foreignId('page_version_id')->constrained('page_versions')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['task_id', 'page_version_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_page_versions');
    }
};


