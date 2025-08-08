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
        // Обновляем существующие страницы для соответствия новой логике версионирования
        $pages = DB::table('pages')->get();
        
        foreach ($pages as $page) {
            // Если у страницы нет base_id, устанавливаем его равным собственному ID
            if (is_null($page->base_id)) {
                DB::table('pages')
                    ->where('id', $page->id)
                    ->update([
                        'base_id' => $page->id,
                        'previous_version_id' => null, // Первая версия
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Восстанавливаем старую логику (не рекомендуется для продакшена)
        $pages = DB::table('pages')->get();
        
        foreach ($pages as $page) {
            // Если base_id равен собственному ID, устанавливаем его в null
            if ($page->base_id == $page->id) {
                DB::table('pages')
                    ->where('id', $page->id)
                    ->update([
                        'base_id' => null,
                    ]);
            }
        }
    }
};
