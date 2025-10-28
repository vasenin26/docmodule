<?php

namespace App\Jobs;

use App\Common\DTO\GeneratorContextDTO;
use App\Common\Enums\AgentTaskType;
use App\Common\Enums\GenerationStatus;
use App\Factory\PromptProviderFactory;
use App\Interfaces\AgentTaskManagerInterface;
use App\Interfaces\Factory\AgentResultHandlerFactoryInterface;
use App\Interfaces\Factory\LLMChatFactoryInterface;
use App\Models\Implementation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessImplementationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $implementationId
    ) {}

    public function handle(
        PromptProviderFactory $promptProviderFactory,
        AgentResultHandlerFactoryInterface $agentResultHandlerFactory,
        AgentTaskManagerInterface $agentTaskManager,
        LLMChatFactoryInterface $chatFactory,
    ): void {
        $implementation = Implementation::with(['techplane.task.pageVersion.page'])->findOrFail($this->implementationId);
        $techplane = $implementation->techplane;
        $task = $techplane->task;

        // Устанавливаем статус "processing"
        $implementation->update(['status' => GenerationStatus::PROCESSING]);

        $promptProvider = $promptProviderFactory->createProjectPromptService($task->project_id);

        // Создаем контекст для реализации техплана
        $context = new GeneratorContextDTO(
            attachedFiles: $task->pageVersion->files ?? [],
            repositories: $task->project->repositories->pluck('url')->toArray(),
            projectId: $task->project_id
        );

        // Создаем чат для реализации
        $chat = $chatFactory->createChatForImplementation(
            $promptProvider,
            $task->project_id,
            $techplane->content, // Содержимое техплана
            $context
        );

        $implementation->chat_id = $chat->id;
        $implementation->save();

        $handler = $agentResultHandlerFactory->createImplementationResultHandler($implementation);

        // Создаем задачу агента с типом CODE для работы с кодом
        $agentTaskManager->createTask(
            $handler,
            $task->created_by,
            $task->project_id,
            $chat->id,
            true,
            AgentTaskType::CODE
        );
    }
}
