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
        Schema::create('implementations', function (Blueprint $table) {
            $table->id();
            $table->text('content')->nullable();
            $table->foreignId('techplane_id')->constrained('techplanes')->onDelete('cascade');
            $table->foreignId('chat_id')->nullable()->constrained('llm_chats')->onDelete('set null');
            $table->string('status')->default('pending');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            // Индексы для оптимизации
            $table->index(['techplane_id', 'status']);
            $table->index(['created_by']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('implementations');
    }
};
