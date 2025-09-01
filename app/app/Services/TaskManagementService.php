<?php

namespace App\Services;


use App\Common\DTO\DifferenceDataDTO;
use App\Factory\ChatFactory;
use App\Factory\PromptProviderFactory;
use App\Interfaces\AgentTaskManagerInterface;
use App\Interfaces\Factory\AgentResultHandlerFactoryInterface;
use App\Interfaces\TaskServiceInterface;
use App\Models\PageVersion;
use App\Models\VersionDiffTask;
use App\Services\DiffGenerator\DiffGeneratorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class TaskManagementService implements TaskServiceInterface
{
    public function __construct(
        private AgentResultHandlerFactoryInterface $agentResultHandlerFactory,
        private AgentTaskManagerInterface          $agentTaskManager,
        private PromptProviderFactory              $promptProviderFactory,
        private DiffGeneratorService               $diffGenerator,
    )
    {
    }

    public function createTaskForPageVersion(
        PageVersion $pageVersion,
        ?int        $userId = null
    ): VersionDiffTask
    {
        $existingTask = VersionDiffTask::where('page_version_id', $pageVersion->id)->first();

        if ($existingTask) {
            throw new \Exception('Для этой версии страницы уже создана задача.');
        }

        $page = $pageVersion->page;

        return DB::transaction(function () use ($pageVersion, $userId, $page) {
            try {
                $versionDiffTask = VersionDiffTask::create([
                    'page_version_id' => $pageVersion->id,
                    'content' => '', // Будет заполнено job'ом
                    'created_by' => $userId ?? Auth::id() ?? $page->created_by,
                    'generation_status' => VersionDiffTask::STATUS_PENDING,
                ]);

                $promptProvider = $this->promptProviderFactory->createProjectPromptService($page->project_id);

                $chat = (new ChatFactory($promptProvider))->createChatForGenerateDescription(
                    $this->createDifferenceDataDTO($pageVersion, $this->diffGenerator),
                    $page->project->repositories->pluck('url')->toArray(),
                    $pageVersion->files
                );

                $handler = $this->agentResultHandlerFactory->createVersionDiffResultHandler($versionDiffTask);
                $this->agentTaskManager->createTask($handler, $page->project_id, $chat->id);
            } catch (\Exception $e) {
                Log::error($e->getMessage());

                throw $e;
            }

            return $versionDiffTask;
        });
    }

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
