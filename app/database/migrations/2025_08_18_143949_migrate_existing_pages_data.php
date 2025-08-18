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
        // Получаем все существующие страницы
        $pages = DB::table('pages')->get();
        
        foreach ($pages as $page) {
            // Создаем запись в page_versions для каждой страницы
            $versionId = DB::table('page_versions')->insertGetId([
                'page_id' => $page->id,
                'title' => $page->title,
                'content' => $page->content,
                'previous_version_id' => $page->previous_version_id,
                'files' => $page->files,
                'created_at' => $page->created_at,
                'updated_at' => $page->updated_at,
            ]);
            
            // Обновляем page_id в page_versions для связи с предыдущей версией
            if ($page->previous_version_id) {
                // Находим ID версии для предыдущей страницы
                $previousVersionId = DB::table('page_versions')
                    ->where('page_id', $page->previous_version_id)
                    ->value('id');
                
                if ($previousVersionId) {
                    DB::table('page_versions')
                        ->where('id', $versionId)
                        ->update(['previous_version_id' => $previousVersionId]);
                }
            }
            
            // Обновляем version_id в таблице pages
            DB::table('pages')
                ->where('id', $page->id)
                ->update(['version_id' => $versionId]);
        }
        
        // Удаляем старые поля из таблицы pages
        Schema::table('pages', function (Blueprint $table) {
            // Сначала удаляем внешние ключи
            $table->dropForeign(['base_id']);
            $table->dropForeign(['previous_version_id']);
            
            // Затем удаляем поля
            $table->dropColumn(['title', 'content', 'files', 'base_id', 'current', 'previous_version_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Восстанавливаем старые поля в таблице pages
        Schema::table('pages', function (Blueprint $table) {
            $table->string('title')->after('id');
            $table->text('content')->nullable()->after('title');
            $table->json('files')->nullable()->after('content');
            $table->foreignId('base_id')->nullable()->constrained('pages')->onDelete('cascade')->after('created_by');
            $table->foreignId('previous_version_id')->nullable()->constrained('pages')->onDelete('cascade')->after('base_id');
            $table->boolean('current')->default(false)->after('parent_id');
            
            // Индексы
            $table->index('current');
            $table->index('base_id');
            $table->index('previous_version_id');
        });
        
        // Восстанавливаем данные из page_versions
        $pageVersions = DB::table('page_versions')->get();
        
        foreach ($pageVersions as $version) {
            $page = DB::table('pages')->where('id', $version->page_id)->first();
            
            if ($page) {
                // Определяем previous_version_id для страницы
                $previousPageId = null;
                if ($version->previous_version_id) {
                    $previousPageId = DB::table('page_versions')
                        ->where('id', $version->previous_version_id)
                        ->value('page_id');
                }
                
                // Определяем base_id и current
                $baseId = $previousPageId ? $page->id : null;
                $current = true; // По умолчанию считаем текущей версией
                
                // Если есть другие версии с тем же page_id, то это не текущая
                $otherVersions = DB::table('page_versions')
                    ->where('page_id', $version->page_id)
                    ->where('id', '!=', $version->id)
                    ->count();
                
                if ($otherVersions > 0) {
                    $current = false;
                }
                
                DB::table('pages')
                    ->where('id', $version->page_id)
                    ->update([
                        'title' => $version->title,
                        'content' => $version->content,
                        'files' => $version->files,
                        'base_id' => $baseId,
                        'previous_version_id' => $previousPageId,
                        'current' => $current,
                    ]);
            }
        }
        
        // Удаляем version_id из таблицы pages
        Schema::table('pages', function (Blueprint $table) {
            $table->dropForeign(['version_id']);
            $table->dropColumn('version_id');
        });
    }
};
