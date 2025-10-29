<?php

namespace App\Jobs;

use App\Common\DTO\Actualization\ActualizationContextDTO;
use App\Common\Enums\AgentTaskType;
use App\Factory\PromptProviderFactory;
use App\Interfaces\AgentTaskManagerInterface;
use App\Interfaces\Factory\AgentResultHandlerFactoryInterface;
use App\Interfaces\Factory\LLMChatFactoryInterface;
use App\Interfaces\HtmlToMdInterface;
use App\Models\Actualization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPageActualizationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Количество попыток выполнения задачи
     */
    public int $tries = 3;

    /**
     * Таймаут выполнения задачи в секундах
     */
    public int $timeout = 600;

    /**
     * Создать новый экземпляр задачи
     */
    public function __construct(
        public int $actualizationId
    ) {
    }

    /**
     * Выполнить задачу
     */
    public function handle(
        PromptProviderFactory $promptProviderFactory,
        AgentResultHandlerFactoryInterface $agentResultHandlerFactory,
        AgentTaskManagerInterface $agentTaskManager,
        LLMChatFactoryInterface $chatFactory,
        HtmlToMdInterface $converter,
    ): void {
        sleep(30);
        $actualization = Actualization::with(['pageVersion.page', 'page'])->findOrFail($this->actualizationId);
        $draft = $actualization->pageVersion;
        $page = $actualization->page;

        $promptProvider = $promptProviderFactory->createProjectPromptService($page->project_id);

        $currentContent = $converter->toMd($draft->content ?? '');

        // Создаем контекст для актуализации
        $context = new ActualizationContextDTO(
            attachedFiles: $draft->files ?? [],
            repositories: $page->project->repositories->pluck('url')->toArray(),
            projectId: $page->project_id
        );

        $chat = $chatFactory->createChatForActualization(
            $promptProvider,
            $page->project_id,
            $currentContent,
            $context
        );

        $actualization->llm_chat_id = $chat->id;
        $actualization->save();

        $handler = $agentResultHandlerFactory->createActualizationResultHandler($actualization);

        $agentTaskManager->createTask($handler, $actualization->created_by, $page->project_id, $chat->id, true, AgentTaskType::ACTUALIZATION);

        $actualization->llm_chat_id = $chat->id;
        $actualization->status = Actualization::STATUS_PENDING;
        $actualization->save();
    }

    /**
     * Обработать неудачное выполнение задачи
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Page actualization job failed', [
            'actualization_id' => $this->actualizationId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        try {
            $actualization = Actualization::find($this->actualizationId);
            if ($actualization && $actualization->status !== Actualization::STATUS_FAILED) {
                $actualization->update(['status' => Actualization::STATUS_FAILED]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update actualization status in failed callback', [
                'actualization_id' => $this->actualizationId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Определить уникальный ID задачи
     */
    public function uniqueId(): string
    {
        return 'process_page_actualization_' . $this->actualizationId;
    }

    /**
     * Получить теги для мониторинга задачи
     */
    public function tags(): array
    {
        return [
            'actualization',
            'page_actualization',
            'actualization_id:' . $this->actualizationId,
        ];
    }
}
