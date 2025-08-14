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
        Schema::table('llm_chats', function (Blueprint $table) {
            // Проверяем существование поля tokens и переименовываем его
            if (Schema::hasColumn('llm_chats', 'tokens')) {
                $table->renameColumn('tokens', 'total_tokens');
            } else {
                $table->integer('total_tokens')->nullable()->after('messages');
            }
            
            // Добавляем новые поля
            $table->integer('prompt_tokens')->nullable()->after('messages');
            $table->integer('completion_tokens')->nullable()->after('prompt_tokens');
            
            // Добавляем индексы для аналитики
            $table->index(['prompt_tokens']);
            $table->index(['completion_tokens']);
            $table->index(['total_tokens']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('llm_chats', function (Blueprint $table) {
            $table->dropIndex(['prompt_tokens']);
            $table->dropIndex(['completion_tokens']);
            $table->dropIndex(['total_tokens']);
            
            $table->dropColumn(['prompt_tokens', 'completion_tokens']);
            $table->renameColumn('total_tokens', 'tokens');
        });
    }
};
