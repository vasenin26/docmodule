<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('version_diff_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('version_diff_tasks', 'project_id')) {
                $table->foreignId('project_id')
                    ->after('id')
                    ->nullable()
                    ->constrained('projects')
                    ->onDelete('cascade');
                $table->index('project_id');
            }
        });

        // Populate project_id for existing records
        DB::statement(<<<SQL
            UPDATE version_diff_tasks vdt
            SET project_id = p.project_id
            FROM page_versions pv, pages p
            WHERE pv.id = vdt.page_version_id 
            AND p.id = pv.page_id
            AND vdt.project_id IS NULL
        SQL);

        // Make project_id NOT NULL after populating
        Schema::table('version_diff_tasks', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('version_diff_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('version_diff_tasks', 'project_id')) {
                $table->dropIndex(['project_id']);
                $table->dropConstrainedForeignId('project_id');
            }
        });
    }
};


