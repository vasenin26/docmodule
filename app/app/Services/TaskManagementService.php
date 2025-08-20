<?php

namespace App\Services;

use App\Jobs\GenerateTaskDescriptionJob;
use App\Models\Page;
use App\Models\PageVersion;
use App\Models\VersionDiffTask;
use Illuminate\Support\Facades\Auth;

class TaskManagementService
{
    /**
     * Создать задачу для версии страницы
     */
    public function createTaskForPageVersion(PageVersion $pageVersion, ?int $userId = null): VersionDiffTask
    {
        // Проверяем, что для версии страницы еще нет задач
        $existingTask = VersionDiffTask::where('page_version_id', $pageVersion->id)->first();
        if ($existingTask) {
            throw new \Exception('Для этой версии страницы уже создана задача.');
        }

        // Создаем запись VersionDiffTask
        $versionDiffTask = VersionDiffTask::create([
            'page_version_id' => $pageVersion->id,
            'content' => '', // Будет заполнено job'ом
            'created_by' => $userId ?? Auth::id() ?? $pageVersion->page->created_by,
            'generation_status' => VersionDiffTask::STATUS_PENDING,
        ]);

        // Запускаем цепочку job'ов
        GenerateTaskDescriptionJob::dispatch($versionDiffTask->id);

        return $versionDiffTask;
    }
    
    /**
     * Проверить можно ли создать задачу для страницы
     */
    public function canCreateTaskForPage(Page $page): bool
    {
        $currentVersion = $page->currentVersion;
        return $currentVersion && 
               $currentVersion->previous_version_id !== null &&
               VersionDiffTask::where('page_version_id', $currentVersion->id)->count() === 0 &&
               !$page->hasActiveDraft();
    }
}
