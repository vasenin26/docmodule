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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('base_id')->nullable()->constrained('pages')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('pages')->onDelete('cascade');
            $table->boolean('current')->default(false);
            $table->timestamps();
            
            // Индексы для оптимизации запросов
            $table->index('current');
            $table->index('parent_id');
            $table->index('created_by');
            $table->index('base_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
