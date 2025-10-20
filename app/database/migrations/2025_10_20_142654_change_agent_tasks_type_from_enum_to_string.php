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
        // Для PostgreSQL: удаляем CHECK constraint и изменяем тип колонки
        if (DB::getDriverName() === 'pgsql') {
            // Удаляем CHECK constraint
            DB::statement("ALTER TABLE agent_tasks DROP CONSTRAINT IF EXISTS agent_tasks_type_check");
            
            // Изменяем тип колонки с enum на varchar
            DB::statement("ALTER TABLE agent_tasks ALTER COLUMN type TYPE VARCHAR(255)");
        } else {
            // Для других БД используем стандартный Laravel подход
            Schema::table('agent_tasks', function (Blueprint $table) {
                $table->string('type', 255)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // Возвращаем CHECK constraint для PostgreSQL
            DB::statement("ALTER TABLE agent_tasks ADD CONSTRAINT agent_tasks_type_check CHECK (type IN ('text','code','actualization'))");
        } else {
            // Для других БД возвращаем enum
            Schema::table('agent_tasks', function (Blueprint $table) {
                $table->enum('type', ['text', 'code', 'actualization'])->change();
            });
        }
    }
};
