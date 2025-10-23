<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_tasks', function (Blueprint $table) {
            // Храним стоимость в единицах RUB * 1000 для повышения точности (3 знака после запятой)
            if (!Schema::hasColumn('agent_tasks', 'cost')) {
                $table->decimal('cost', 20, 3)->nullable()->comment('Стоимость задачи, хранится как RUB * 1000 для точности');
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
