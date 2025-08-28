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
        Schema::create('agent_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('handler', 255)->comment('Полное имя класса обработчика результата');
            $table->json('handler_options')->nullable()->comment('Параметры для обработчика в JSON формате');
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('chat_id')->constrained('llm_chats')->onDelete('cascade');
            $table->enum('status', ['wait', 'processing', 'success', 'failed'])
                  ->default('wait')
                  ->comment('Статус выполнения задачи');
            $table->string('agent_id', 36)->nullable()->comment('UUID агента, работающего с задачей');
            $table->timestamps();

            // Составные индексы для оптимизации запросов
            $table->index(['status', 'created_at'], 'idx_status_created');
            $table->index(['project_id', 'status'], 'idx_project_status');
            $table->index('updated_at', 'idx_updated_at');
            $table->index('agent_id', 'idx_agent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_tasks');
    }
};
