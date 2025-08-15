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
        Schema::create('techplanes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('page_diff_descriptions')->onDelete('cascade');
            $table->text('content')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('chat_id')->nullable()->constrained('llm_chats')->onDelete('set null');
            $table->string('generation_status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('techplanes');
    }
};
