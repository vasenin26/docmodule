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
        Schema::create('actualizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->onDelete('cascade');
            // Используем простой ключ без FK, так как порядок миграций может отличаться
            $table->unsignedBigInteger('page_version_id');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->foreignId('llm_chat_id')->nullable()->constrained('llm_chats')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['page_id']);
            $table->index(['page_version_id']);
            $table->index(['status']);
            $table->index(['created_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actualizations');
    }
};
