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
        // Удаляем записи без привязки к черновику (page_version_id IS NULL)
        DB::table('actualizations')->whereNull('page_version_id')->delete();

        // Ищем дубликаты по page_version_id
        $duplicateVersionIds = DB::table('actualizations')
            ->select('page_version_id', DB::raw('COUNT(*)'))
            ->whereNotNull('page_version_id')
            ->groupBy('page_version_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('page_version_id');

        foreach ($duplicateVersionIds as $versionId) {
            DB::transaction(function () use ($versionId) {
                // Определяем одну лучшую запись для сохранения
                $keep = DB::table('actualizations')
                    ->where('page_version_id', $versionId)
                    ->orderByRaw("CASE WHEN status IN ('processing','pending') THEN 0 ELSE 1 END")
                    ->orderByDesc('updated_at')
                    ->orderByDesc('created_at')
                    ->first();

                if (!$keep) {
                    return;
                }

                $toDelete = DB::table('actualizations')
                    ->where('page_version_id', $versionId)
                    ->where('id', '!=', $keep->id)
                    ->get();

                foreach ($toDelete as $row) {
                    // Если есть связанный чат — удаляем связанные задачи и сам чат
                    if (!is_null($row->llm_chat_id)) {
                        DB::table('agent_tasks')->where('chat_id', $row->llm_chat_id)->delete();
                        DB::table('llm_chats')->where('id', $row->llm_chat_id)->delete();
                    }
                    // Удаляем саму запись актуализации
                    DB::table('actualizations')->where('id', $row->id)->delete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Невозможно безопасно восстановить удаленные дубликаты
    }
};
