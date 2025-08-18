<?php

namespace App\Services;

use App\Jobs\GenerateTaskDescriptionJob;
use App\Models\Page;
use App\Models\PageDiffDescription;
use Illuminate\Support\Facades\Auth;

class TaskManagementService
{
    /**
     * Создать задачу для страницы
     */
    public function createTaskForPage(Page $page, ?int $userId = null): PageDiffDescription
    {
        // Проверяем, что для страницы еще нет задач
        $existingTask = PageDiffDescription::where('page_id', $page->id)->first();
        if ($existingTask) {
            throw new \Exception('Для этой страницы уже создана задача.');
        }
        
        // Проверяем, что есть предыдущая версия для сравнения
        $currentVersion = $page->currentVersion;
        if (!$currentVersion || !$currentVersion->previous_version_id) {
            throw new \Exception('Невозможно создать задачу для первой версии страницы.');
        }

        // Создаем запись PageDiffDescription
        $diffDescription = PageDiffDescription::create([
            'page_id' => $page->id,
            'content' => '', // Будет заполнено job'ом
            'created_by' => $userId ?? Auth::id() ?? $page->created_by,
        ]);

        // Запускаем цепочку job'ов
        GenerateTaskDescriptionJob::dispatch($diffDescription->id);

        return $diffDescription;
    }
    
    /**
     * Проверить можно ли создать задачу для страницы
     */
    public function canCreateTaskForPage(Page $page): bool
    {
        $currentVersion = $page->currentVersion;
        return $currentVersion && 
               $currentVersion->previous_version_id !== null &&
               PageDiffDescription::where('page_id', $page->id)->count() === 0 &&
               !$page->hasActiveDraft();
    }
}
