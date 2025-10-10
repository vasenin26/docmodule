<?php

namespace App\Services;

use App\Models\Actualization;
use App\Models\AgentTask;
use App\Models\Page;
use App\Models\PageVersion;
use App\Models\User;
use App\Jobs\ProcessPageActualizationJob;
use Illuminate\Support\Facades\Log;

class ActualizationService
{
    public function __construct()
    {
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

        // Найти существующую актуализацию по page_version_id
        $actualization = Actualization::where('page_version_id', $draft->id)->first();

        // Если существует и уже генерируется — ошибка
        if ($actualization && $actualization->isGenerating()) {
            throw new \RuntimeException('Для этого черновика уже есть активная актуализация');
        }

        // Получить страницу для логирования
        $page = $draft->page;

        if ($actualization) {
            // Переиспользуем запись: переводим в pending и создаем новый чат
            $chat = new \App\Models\LLMChat();
            $chat->messages = [];
            $chat->save();

            $actualization->update([
                'status' => Actualization::STATUS_PENDING,
                'llm_chat_id' => $chat->id,
            ]);
        } else {
            // Создаем новую запись
            $actualization = Actualization::create([
                'page_id' => $page->id,
                'page_version_id' => $draft->id,
                'status' => Actualization::STATUS_PENDING,
                'created_by' => $user->id,
            ]);
        }

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

    /**
     * Получить статус актуализации с данными чата
     */
    public function getStatusWithChat(Actualization $actualization): array
    {
        $actualization->load(['llmChat', 'createdBy', 'pageVersion']);

        // Агрегированный статус и признак активной задачи агента
        $aggregatedStatus = $actualization->generationStatus();
        $hasActiveAgentTask = false;
        if ($actualization->llm_chat_id) {
            $hasActiveAgentTask = (bool) AgentTask::where('chat_id', $actualization->llm_chat_id)
                ->whereIn('status', [AgentTask::STATUS_WAIT, AgentTask::STATUS_PROCESSING])
                ->exists();
        }

        return [
            'id' => $actualization->id,
            'status' => $aggregatedStatus,
            'content' => $actualization->pageVersion->content ?? '',
            'chat' => $actualization->llmChat ? [
                'id' => $actualization->llmChat->id,
                'messages' => $actualization->llmChat->messages,
                'context_fill' => $actualization->llmChat->context_fill,
            ] : null,
            'has_active_agent_task' => $hasActiveAgentTask,
            'created_at' => $actualization->created_at,
            'updated_at' => $actualization->updated_at,
            'created_by' => $actualization->createdBy->name ?? 'Unknown',
        ];
    }
}
