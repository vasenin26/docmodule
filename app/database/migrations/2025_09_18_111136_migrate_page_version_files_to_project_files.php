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
        DB::transaction(function () {
            $versions = DB::table('page_versions')
                ->select('id', 'page_id', 'files')
                ->whereNotNull('files')
                ->get();

            foreach ($versions as $version) {
                $files = json_decode($version->files, true) ?: [];
                if (!is_array($files) || empty($files)) {
                    continue;
                }

                $projectId = DB::table('pages')->where('id', $version->page_id)->value('project_id');
                if (!$projectId) {
                    continue;
                }

                $now = now();
                $upsertRows = [];
                foreach ($files as $url) {
                    if (!is_string($url)) {
                        continue;
                    }
                    $upsertRows[] = [
                        'project_id' => $projectId,
                        'url' => $url,
                        'description' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($upsertRows) {
                    DB::table('project_files')->upsert($upsertRows, ['project_id', 'url'], ['description', 'updated_at']);

                    $urls = array_column($upsertRows, 'url');
                    $projectFileIds = DB::table('project_files')
                        ->where('project_id', $projectId)
                        ->whereIn('url', $urls)
                        ->pluck('id')
                        ->all();

                    foreach ($projectFileIds as $projectFileId) {
                        DB::table('project_file_page_version')->updateOrInsert([
                            'page_version_id' => $version->id,
                            'project_file_id' => $projectFileId,
                        ], []);
                    }
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // no-op
    }
};
