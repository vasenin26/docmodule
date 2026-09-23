<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Добавляем статусы init и restarting, используемые в ActualizationService
        DB::statement("ALTER TABLE actualizations DROP CONSTRAINT IF EXISTS actualizations_status_check");
        DB::statement("ALTER TABLE actualizations ADD CONSTRAINT actualizations_status_check CHECK (status IN ('pending', 'processing', 'completed', 'failed', 'restarting', 'init'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE actualizations DROP CONSTRAINT IF EXISTS actualizations_status_check");
        DB::statement("ALTER TABLE actualizations ADD CONSTRAINT actualizations_status_check CHECK (status IN ('pending', 'processing', 'completed', 'failed'))");
    }
};
