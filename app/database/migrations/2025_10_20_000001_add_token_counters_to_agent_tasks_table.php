<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('agent_tasks', 'prompt_tokens')) {
                $table->unsignedInteger('prompt_tokens')->nullable()->default(0)->after('agent_model');
            }
            if (!Schema::hasColumn('agent_tasks', 'completion_tokens')) {
                $table->unsignedInteger('completion_tokens')->nullable()->default(0)->after('prompt_tokens');
            }
            if (!Schema::hasColumn('agent_tasks', 'total_tokens')) {
                $table->unsignedInteger('total_tokens')->nullable()->default(0)->after('completion_tokens');
            }
        });

        // Initialize existing records to 0
        try {
            \Illuminate\Support\Facades\DB::table('agent_tasks')->update([
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_tokens' => 0,
            ]);
        } catch (\Throwable $e) {
            // No-op: avoid failing migration if table empty or columns missing in some environments
        }
    }

    public function down(): void
    {
        Schema::table('agent_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('agent_tasks', 'total_tokens')) {
                $table->dropColumn('total_tokens');
            }
            if (Schema::hasColumn('agent_tasks', 'completion_tokens')) {
                $table->dropColumn('completion_tokens');
            }
            if (Schema::hasColumn('agent_tasks', 'prompt_tokens')) {
                $table->dropColumn('prompt_tokens');
            }
        });
    }
};


