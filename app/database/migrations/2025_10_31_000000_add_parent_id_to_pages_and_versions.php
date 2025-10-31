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
        // Add parent_id to pages if it does not exist
        if (Schema::hasTable('pages')) {
            if (!Schema::hasColumn('pages', 'parent_id')) {
                Schema::table('pages', function (Blueprint $table) {
                    $table->unsignedBigInteger('parent_id')->nullable()->after('id')->index();
                    $table->foreign('parent_id')->references('id')->on('pages')->onDelete('set null');
                });
            }
        }

        // Add parent_id to page_versions if it does not exist
        if (Schema::hasTable('page_versions')) {
            if (!Schema::hasColumn('page_versions', 'parent_id')) {
                Schema::table('page_versions', function (Blueprint $table) {
                    $table->unsignedBigInteger('parent_id')->nullable()->after('id')->index();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove parent_id from page_versions if exists
        if (Schema::hasTable('page_versions')) {
            if (Schema::hasColumn('page_versions', 'parent_id')) {
                Schema::table('page_versions', function (Blueprint $table) {
                    // drop index if exists
                    try {
                        $table->dropIndex(['parent_id']);
                    } catch (\Exception $e) {
                        // ignore
                    }
                    $table->dropColumn('parent_id');
                });
            }
        }

        // Remove parent_id from pages if exists
        if (Schema::hasTable('pages')) {
            if (Schema::hasColumn('pages', 'parent_id')) {
                Schema::table('pages', function (Blueprint $table) {
                    // drop foreign and index if exist
                    try {
                        $table->dropForeign(['parent_id']);
                    } catch (\Exception $e) {
                        // ignore
                    }
                    try {
                        $table->dropIndex(['parent_id']);
                    } catch (\Exception $e) {
                        // ignore
                    }
                    $table->dropColumn('parent_id');
                });
            }
        }
    }
};
