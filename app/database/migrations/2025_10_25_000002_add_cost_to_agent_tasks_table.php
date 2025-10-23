<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_tasks', function (Blueprint $table) {
            // Храним стоимость в виде целого (int) как RUB * 1000 для повышения точности (сохранение в миллибаблях)
            if (!Schema::hasColumn('agent_tasks', 'cost')) {
                $table->unsignedBigInteger('cost')->nullable()->comment('Стоимость задачи, хранится как целое: RUB * 1000');
            }
        });
    }

    public function down(): void
    {
        Schema::table('agent_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('agent_tasks', 'cost')) {
                $table->dropColumn('cost');
            }
        });
    }
};
