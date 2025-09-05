<?php

namespace App\Jobs;

use App\Common\DTO\DifferenceDataDTO;
use App\Factory\PromptProviderFactory;
use App\Interfaces\AgentTaskManagerInterface;
use App\Interfaces\Factory\AgentResultHandlerFactoryInterface;
use App\Interfaces\Factory\LLMChatFactoryInterface;
use App\Models\VersionDiffTask;
use App\Services\DiffGenerator\DiffGeneratorService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateTaskDescriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    public $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $versionDiffTaskId
    )
    {
    }

    /**
     * Execute the job.
     */
    public function handle(
        PromptProviderFactory              $promptProviderFactory,
        DiffGeneratorService               $diffGenerator,
        AgentResultHandlerFactoryInterface $agentResultHandlerFactory,
        AgentTaskManagerInterface          $agentTaskManager,
        LLMChatFactoryInterface           $chatFactory,
    ): void
    {
        $versionDiffTask = VersionDiffTask::with(['pageVersion.page', 'pageVersion.previousVersion'])->findOrFail($this->versionDiffTaskId);
        $page = $versionDiffTask->pageVersion->page;
        $pageVersion = $versionDiffTask->pageVersion;

        $promptProvider = $promptProviderFactory->createProjectPromptService($page->project_id);

        $chat = $chatFactory->createChatForGenerateDescription(
            $promptProvider,
            $this->createDifferenceDataDTO($pageVersion, $diffGenerator),
            $page->project->repositories->pluck('url')->toArray(),
            $pageVersion->files
        );

        $versionDiffTask->llm_chat_id = $chat->id;
        $versionDiffTask->save();

        $handler = $agentResultHandlerFactory->createVersionDiffResultHandler($versionDiffTask);

        $agentTaskManager->createTask($handler, $versionDiffTask->created_by, $page->project_id, $chat->id);
    }

    /**
     * Создание DifferenceDataDTO на основе информации о версии страницы
     */
    private function createDifferenceDataDTO($currentVersion, DiffGeneratorService $diffGenerator): DifferenceDataDTO
    {
        $previousVersion = $currentVersion->previousVersion;

        if (!$previousVersion) {
            // Новая страница
            return new DifferenceDataDTO(
                diffOutput: null,
                newVersionTitle: $currentVersion->title,
                isNewPage: true,
                titleChanged: false,
                contentChanged: false,
                newVersionId: $currentVersion->id,
                newVersionContent: $currentVersion->content,
                previousVersionId: null,
                previousVersionTitle: null,
                previousVersionContent: null
            );
        }

        // Обновленная страница
        $titleChanged = $currentVersion->title !== $previousVersion->title;
        $contentChanged = $currentVersion->content !== $previousVersion->content;

        // Генерируем diff если есть изменения
        $diffOutput = null;
        if ($titleChanged || $contentChanged) {
            $diffOutput = $diffGenerator->generateDiff($previousVersion->content, $currentVersion->content);
        }

        return new DifferenceDataDTO(
            diffOutput: $diffOutput,
            newVersionTitle: $currentVersion->title,
            isNewPage: false,
            titleChanged: $titleChanged,
            contentChanged: $contentChanged,
            newVersionId: $currentVersion->id,
            newVersionContent: $currentVersion->content,
            previousVersionId: $previousVersion->id,
            previousVersionTitle: $previousVersion->title,
            previousVersionContent: $previousVersion->content
        );
    }


}
