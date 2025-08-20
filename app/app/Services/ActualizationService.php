<?php

namespace App\Services;

use App\Factory\AgentFactory;
use App\Models\Actualization;
use App\Models\Page;
use App\Models\PageVersion;
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
     * Инициировать процесс актуализации для конкретного черновика
     */
    public function initiate(PageVersion $draft, User $user): Actualization
    {
        // Проверить, что это черновик
        if (!$draft->is_draft) {
            throw new \RuntimeException('Актуализация возможна только для черновиков');
        }

        // Проверить, нет ли активной актуализации для этого черновика
        if ($draft->hasActiveActualization()) {
            throw new \RuntimeException('Для этого черновика уже есть активная актуализация');
        }

        // Получить страницу для логирования
        $page = $draft->page;

        // Создать запись актуализации - ИСПРАВЛЕНИЕ: привязка к черновику
        $actualization = Actualization::create([
            'page_id' => $page->id,           // ID страницы
            'page_version_id' => $draft->id,  // ID черновика - ОСНОВНАЯ СВЯЗЬ
            'status' => Actualization::STATUS_PENDING,
            'created_by' => $user->id,
        ]);

        // Запустить фоновую задачу
        ProcessPageActualizationJob::dispatch($actualization->id);

        Log::info('Actualization initiated', [
            'actualization_id' => $actualization->id,
            'page_id' => $page->id,
            'page_version_id' => $draft->id,
            'base_version_id' => $draft->previous_version_id, // Логируем базовую версию
            'user_id' => $user->id,
        ]);

        return $actualization;
    }

    /**
     * Удобный метод для создания черновика и запуска актуализации
     */
    public function initiateForCurrentVersion(Page $page, User $user): Actualization
    {
        // Получить текущую версию страницы
        $currentVersion = $page->currentVersion;
        if (!$currentVersion) {
            throw new \RuntimeException('Страница не имеет текущей версии');
        }

        // Создать черновик из текущей версии страницы
        $draft = $page->createDraft();

        // Запустить актуализацию для созданного черновика
        return $this->initiate($draft, $user);
    }

    /**
     * Обработать актуализацию
     */
    public function process(Actualization $actualization): void
    {
        try {
            // Обновить статус на processing
            $actualization->update(['status' => Actualization::STATUS_PROCESSING]);

            // Получить черновик через новую связь
            $draft = $actualization->pageVersion;
            if (!$draft) {
                throw new \RuntimeException('Черновик не найден для актуализации');
            }

            $page = $actualization->page;

            // Получить генератор актуализации
            $generator = $this->getActualizationGenerator($page->project_id);

            // Запустить генерацию актуализации
            // Черновик уже содержит актуальный контент и файлы из текущей версии
            $result = $generator->actualize(
                $draft->content ?? '',
                $draft->files ?? []
            );

            // Сохранить результат в черновике
            $draft->update([
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
                'page_version_id' => $draft->id,
                'chat_id' => $result->chatId,
            ]);

        } catch (\Exception $e) {
            // Обновить статус на failed
            $actualization->update(['status' => Actualization::STATUS_FAILED]);

            Log::error('Actualization failed', [
                'actualization_id' => $actualization->id,
                'page_id' => $actualization->page_id,
                'page_version_id' => $actualization->page_version_id,
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
        $actualization->load(['page', 'pageVersion', 'llmChat', 'createdBy']);

        return [
            'id' => $actualization->id,
            'status' => $actualization->status,
            'created_at' => $actualization->created_at,
            'updated_at' => $actualization->updated_at,
            'created_by' => $actualization->createdBy,
            'page' => [
                'id' => $actualization->page->id,
                'title' => $actualization->page->title,
            ],
            'draft' => [
                'id' => $actualization->pageVersion->id,
                'title' => $actualization->pageVersion->title,
                'content' => $actualization->pageVersion->content,
                'files' => $actualization->pageVersion->files,
                'is_draft' => $actualization->pageVersion->is_draft,
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
