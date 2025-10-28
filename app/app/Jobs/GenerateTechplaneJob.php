<?php

namespace App\Jobs;

use App\Common\DTO\GeneratorContextDTO;
use App\Common\Enums\AgentTaskType;
use App\Factory\PromptProviderFactory;
use App\Interfaces\AgentTaskManagerInterface;
use App\Interfaces\Factory\AgentResultHandlerFactoryInterface;
use App\Interfaces\Factory\LLMChatFactoryInterface;
use App\Models\AgentTask;
use App\Models\Techplane;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateTechplaneJob implements ShouldQueue
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
        public int $techplaneId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        PromptProviderFactory $promptProviderFactory,
        AgentResultHandlerFactoryInterface $agentResultHandlerFactory,
        AgentTaskManagerInterface $agentTaskManager,
        LLMChatFactoryInterface $chatFactory,
    ): void {
        $techplane = Techplane::with(['task.pageVersion.page'])->findOrFail($this->techplaneId);

        if($techplane->chat_id !== null) {
            Log::info('Techplane have active chat', [$techplane->chat_id]);
            AgentTask::stopGeneratingForChat($techplane->chat_id, $agentTaskManager);
        }

        $task = $techplane->task;

        // Устанавливаем статус "generating"
        $techplane->update(['generation_status' => Techplane::STATUS_GENERATING]);

        $promptProvider = $promptProviderFactory->createProjectPromptService($task->project_id);

        $taskDescription = $task->content ?? 'Описание задачи отсутствует';

        // Создаем контекст для генерации
        $context = new GeneratorContextDTO(
            attachedFiles: $task->pageVersion->files ?? [],
            repositories: $task->project->repositories->pluck('url')->toArray(),
            projectId: $task->project_id
        );

        $chat = $chatFactory->createChatForTechplane(
            $promptProvider,
            $task->project_id,
            $taskDescription,
            $context
        );

        $techplane->chat_id = $chat->id;
        $techplane->save();

        $handler = $agentResultHandlerFactory->createTechplaneResultHandler($techplane);

        $agentTaskManager->createTask($handler, $task->created_by, $task->project_id, $chat->id, true, AgentTaskType::TECH);
    }
}
