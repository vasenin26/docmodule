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
        Schema::table('actualizations', function (Blueprint $table) {
            $table->unique('page_version_id', 'actualizations_page_version_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('actualizations', function (Blueprint $table) {
            $table->dropUnique('actualizations_page_version_unique');
        });
    }
};
