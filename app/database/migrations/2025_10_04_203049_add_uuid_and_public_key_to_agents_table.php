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
        Schema::table('agents', function (Blueprint $table) {
            // UUID для идентификации агента в оркестраторе
            $table->uuid('uuid')
                ->nullable()
                ->after('id')
                ->unique()
                ->comment('UUID агента для идентификации в оркестраторе');
            
            // Публичный ключ, полученный от оркестратора
            $table->text('public_key')
                ->nullable()
                ->after('token')
                ->comment('Публичный ключ агента, полученный от оркестратора');
            
            // Индексы
            $table->index('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropIndex(['uuid']);
            $table->dropColumn(['uuid', 'public_key']);
        });
    }
};
