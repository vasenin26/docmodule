<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration not required: cost column for AgentTask is already handled by agent_tasks migration (2025_10_25_000002_add_cost_to_agent_tasks_table.php).
// This file was created earlier by automation but the technical plan was amended: changes to 'tasks' table are not needed.
// Keep a no-op migration to avoid accidental application.

return new class extends Migration

{
    public function up(): void
    {
        Schema::table('agent_tasks', function (Blueprint $table) {
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
