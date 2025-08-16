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
            $table->timestamp('edited_at')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('page_diff_descriptions', function (Blueprint $table) {
            $table->dropIndex(['edited_at']);
            $table->dropColumn('edited_at');
        });
    }
};
