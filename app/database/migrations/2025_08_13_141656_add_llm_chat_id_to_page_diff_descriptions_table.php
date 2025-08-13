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
        Schema::table('page_diff_descriptions', function (Blueprint $table) {
            $table->foreignId('llm_chat_id')->nullable()->constrained('llm_chats')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('page_diff_descriptions', function (Blueprint $table) {
            $table->dropForeign(['llm_chat_id']);
            $table->dropColumn('llm_chat_id');
        });
    }
};
