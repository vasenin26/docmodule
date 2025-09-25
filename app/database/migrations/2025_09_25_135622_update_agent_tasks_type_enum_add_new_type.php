<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // PostgreSQL: recreate CHECK constraint with the new value
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE agent_tasks DROP CONSTRAINT IF EXISTS agent_tasks_type_check");
            DB::statement("ALTER TABLE agent_tasks ADD CONSTRAINT agent_tasks_type_check CHECK (type IN ('text','code','actualization'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // Normalize values first
            DB::table('agent_tasks')
                ->where('type', 'actualization')
                ->update(['type' => 'text']);

            DB::statement("ALTER TABLE agent_tasks DROP CONSTRAINT IF EXISTS agent_tasks_type_check");
            DB::statement("ALTER TABLE agent_tasks ADD CONSTRAINT agent_tasks_type_check CHECK (type IN ('text','code'))");
        }
    }
};
