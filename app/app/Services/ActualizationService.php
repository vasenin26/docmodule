<?php

namespace App\Services;

use App\Factory\AgentFactory;
use App\Models\Actualization;
use App\Models\Page;
use App\Models\User;
use App\Jobs\ProcessPageActualizationJob;
use App\Interfaces\ContentGenerator\ActualizationGeneratorInterface;
use Illuminate\Support\Facades\Log;

class ActualizationService
{
    public function __construct(
        private AgentFactory $agentFactory,
    ) {
    }

    /**
     * Инициировать процесс актуализации
     */
    public function initiate(Page $page, User $user): Actualization
    {
        // Проверить, нет ли активной актуализации
        if ($page->hasActiveActualization()) {
            throw new \RuntimeException('У страницы уже есть активная актуализация');
        }

        // Создать черновик страницы
        $draft = $page->createDraft();

        // Создать запись актуализации
        $actualization = Actualization::create([
            'page_id' => $draft->id,
            'status' => Actualization::STATUS_PENDING,
            'created_by' => $user->id,
        ]);

        // Запустить фоновую задачу
        ProcessPageActualizationJob::dispatch($actualization->id);

        Log::info('Actualization initiated', [
            'actualization_id' => $actualization->id,
            'page_id' => $page->id,
            'draft_id' => $draft->id,
            'user_id' => $user->id,
        ]);

        return $actualization;
    }

    /**
     * Обработать актуализацию
     */
    public function process(Actualization $actualization): void
    {
        try {
            // Обновить статус на processing
            $actualization->update(['status' => Actualization::STATUS_PROCESSING]);

            $page = $actualization->page;
            
            // Получить базовую страницу для анализа файлов
            $basePage = $page->previousVersion ?? $page;
            
            // Получить генератор актуализации
            $generator = $this->getActualizationGenerator($basePage->project_id);
            
            // Запустить генерацию
            $result = $generator->actualize(
                $page->content,
                $page->files ?? []
            );

            // Сохранить результат в черновике
            $page->update([
                'content' => $result->result,
            ]);

            // Прикрепить чат к актуализации (если есть)
            if ($result->chatId) {
                $actualization->update(['llm_chat_id' => $result->chatId]);
            }

            // Обновить статус актуализации на completed
            $actualization->update(['status' => Actualization::STATUS_COMPLETED]);

            Log::info('Actualization completed successfully', [
                'actualization_id' => $actualization->id,
                'page_id' => $page->id,
                'chat_id' => $result->chatId,
            ]);

        } catch (\Exception $e) {
            // Обновить статус на failed
            $actualization->update(['status' => Actualization::STATUS_FAILED]);

            Log::error('Actualization failed', [
                'actualization_id' => $actualization->id,
                'page_id' => $actualization->page_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Получить статус актуализации для страницы
     */
    public function getStatus(Page $page): ?array
    {
        $latestActualization = $page->latestActualization;
        
        if (!$latestActualization) {
            return null;
        }

        return [
            'id' => $latestActualization->id,
            'status' => $latestActualization->status,
            'created_at' => $latestActualization->created_at,
            'updated_at' => $latestActualization->updated_at,
            'created_by' => $latestActualization->createdBy->name ?? 'Unknown',
            'has_chat' => !is_null($latestActualization->llm_chat_id),
        ];
    }

    /**
     * Получить детали актуализации
     */
    public function getDetails(Actualization $actualization): array
    {
        $actualization->load(['page', 'llmChat', 'createdBy']);
        
        return [
            'id' => $actualization->id,
            'status' => $actualization->status,
            'created_at' => $actualization->created_at,
            'updated_at' => $actualization->updated_at,
            'created_by' => $actualization->createdBy,
            'page' => [
                'id' => $actualization->page->id,
                'title' => $actualization->page->title,
                'content' => $actualization->page->content,
                'files' => $actualization->page->files,
            ],
            'chat' => $actualization->llmChat ? [
                'id' => $actualization->llmChat->id,
                'messages' => $actualization->llmChat->messages,
                'prompt_tokens' => $actualization->llmChat->prompt_tokens,
                'completion_tokens' => $actualization->llmChat->completion_tokens,
                'total_tokens' => $actualization->llmChat->total_tokens,
            ] : null,
        ];
    }

    /**
     * Получить генератор актуализации для проекта
     */
    private function getActualizationGenerator(int $projectId): ActualizationGeneratorInterface
    {
        return $this->agentFactory->getActualizationGenerator($projectId);
    }

    /**
     * Отменить актуализацию
     */
    public function cancel(Actualization $actualization): void
    {
        if (!in_array($actualization->status, [Actualization::STATUS_PENDING, Actualization::STATUS_PROCESSING])) {
            throw new \RuntimeException('Невозможно отменить актуализацию со статусом: ' . $actualization->status);
        }

        $actualization->update(['status' => Actualization::STATUS_FAILED]);

        Log::info('Actualization cancelled', [
            'actualization_id' => $actualization->id,
            'page_id' => $actualization->page_id,
        ]);
    }
}
