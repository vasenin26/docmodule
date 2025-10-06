<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'public_key')) {
                $table->text('public_key')->nullable()
                    ->after('owner_id')
                    ->comment('SSH публичный ключ проекта');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'public_key')) {
                $table->dropColumn('public_key');
            }
        });
    }
};
