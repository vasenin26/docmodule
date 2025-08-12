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
        Schema::table('page_diff_descriptions', function (Blueprint $table) {
            $table->enum('generation_status', ['pending', 'generating', 'completed', 'failed'])
                  ->default('pending')
                  ->after('content');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('page_diff_descriptions', function (Blueprint $table) {
            $table->dropColumn('generation_status');
        });
    }
};
