<?php

namespace App\Services;

use App\Interfaces\AgentTaskManagerInterface;
use App\Interfaces\Factory\AgentResultHandlerFactoryInterface;
use App\Interfaces\Factory\LLMChatFactoryInterface;
use App\Models\Page;
use App\Models\PageVersion;
use App\Models\VersionDiffTask;
use Illuminate\Support\Facades\Auth;

class TaskManagementService
{
    /**
     * Создать задачу для версии страницы
     */
    public function createTaskForPageVersion(
        AgentResultHandlerFactoryInterface $agentResultHandlerFactory,
        AgentTaskManagerInterface $agentTaskManager,
        LLMChatFactoryInterface $chatFactory,
        PageVersion $pageVersion,
        ?int $userId = null
    ): VersionDiffTask
    {
        $existingTask = VersionDiffTask::where('page_version_id', $pageVersion->id)->first();
        if ($existingTask) {
            throw new \Exception('Для этой версии страницы уже создана задача.');
        }

        $versionDiffTask = VersionDiffTask::create([
            'page_version_id' => $pageVersion->id,
            'content' => '', // Будет заполнено job'ом
            'created_by' => $userId ?? Auth::id() ?? $pageVersion->page->created_by,
            'generation_status' => VersionDiffTask::STATUS_PENDING,
        ]);

        $chat = $chatFactory->createChatForGenerateDescription($versionDiffTask);
        $handler = $agentResultHandlerFactory->createVersionDiffResultHandler($versionDiffTask);
        $agentTaskManager->createTask($handler, $pageVersion->page->projectId, $chat->id);

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
