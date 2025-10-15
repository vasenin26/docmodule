<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('agent_tasks', 'agent_model')) {
                $table->string('agent_model', 255)
                    ->nullable()
                    ->after('type')
                    ->comment('Preferred agent LLM model name for this task');
            }
        });
    }

    public function down(): void
    {
        Schema::table('agent_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('agent_tasks', 'agent_model')) {
                $table->dropColumn('agent_model');
            }
        });
    }
};


