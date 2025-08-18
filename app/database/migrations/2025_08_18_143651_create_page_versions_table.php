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
        Schema::create('page_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->onDelete('cascade');
            $table->string('title');
            $table->text('content')->nullable();
            $table->foreignId('previous_version_id')->nullable()->constrained('page_versions')->onDelete('cascade');
            $table->json('files')->nullable();
            $table->timestamps();
            
            // Индексы для оптимизации запросов
            $table->index('page_id');
            $table->index('previous_version_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_versions');
    }
};
