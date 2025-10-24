<?php

namespace App\Jobs;

use App\Common\DTO\DifferenceDataDTO;
use App\Common\Enums\AgentTaskType;
use App\Factory\PromptProviderFactory;
use App\Interfaces\AgentTaskManagerInterface;
use App\Interfaces\Factory\AgentResultHandlerFactoryInterface;
use App\Interfaces\Factory\LLMChatFactoryInterface;
use App\Models\AgentTask;
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
        AgentResultHandlerFactoryInterface $agentResultHandlerFactory,
        AgentTaskManagerInterface          $agentTaskManager,
        LLMChatFactoryInterface            $chatFactory,
    ): void
    {
        $versionDiffTask = VersionDiffTask::with(['pageVersion.page', 'pageVersion.previousVersion', 'pageVersions.page'])->findOrFail($this->versionDiffTaskId);

        if($versionDiffTask->chat_id !== null) {
            Log::info('Techplane have active chat', [$versionDiffTask->chat_id]);
            AgentTask::stopGeneratingForChat($versionDiffTask->chat_id, $agentTaskManager);
        }

        // Сбросить статус и контент
        $versionDiffTask->update([
            'generation_status' => VersionDiffTask::STATUS_PENDING,
            'content' => null,
            'llm_chat_id' => null
        ]);

        $promptProvider = $promptProviderFactory->createProjectPromptService($versionDiffTask->project_id);

        $chat = $chatFactory->createChatForGenerateDescription(
            $promptProvider,
            $versionDiffTask,
        );

        $versionDiffTask->llm_chat_id = $chat->id;
        $versionDiffTask->save();

        $handler = $agentResultHandlerFactory->createVersionDiffResultHandler($versionDiffTask);

        $agentTaskManager->createTask($handler, $versionDiffTask->created_by, $versionDiffTask->project_id, $chat->id, true, AgentTaskType::TASK);
    }
}
