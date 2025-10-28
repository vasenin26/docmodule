<?php

namespace App\Models;

use App\Interfaces\AgentTaskManagerInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LLMChat extends Model
{
    use HasFactory;

    const ROLE_SYSTEM = 'system';
    const ROLE_USER = 'user';


    protected $table = 'llm_chats';

    protected $fillable = [
        'messages',
        'context',
        'context_fill',
    ];

    public function getStatus(): string
    {
        $activeAgentTaskCount = AgentTask::where('chat_id', $this->id)
            ->whereIn('status', [AgentTask::STATUS_WAIT, AgentTask::STATUS_PROCESSING])
            ->count();

        return $activeAgentTaskCount ? 'processing' : 'completed';
    }

    public function stopGeneration(
        AgentTaskManagerInterface $agentTaskManager,
    )
    {
        $activeAgentTasks = AgentTask::where('chat_id', $this->id)
            ->whereIn('status', [AgentTask::STATUS_WAIT, AgentTask::STATUS_PROCESSING])
            ->all();

        $activeAgentTasks->each(function (AgentTask $agentTask) use ($agentTaskManager) {
            $agentTaskManager->stopTask($agentTask->id);
        });
    }

    protected function casts(): array
    {
        return [
            'messages' => 'array',
            'context' => 'array',
            'context_fill' => 'float',
        ];
    }

    protected static function booted(): void
    {
    }

    /**
     * Проверка был ли рассчитан размер токенов (любого типа)
     *
     * @return bool
     */
    public function isTokensCalculated(): bool
    {
        // Токены теперь хранятся в связанных задачах (agent_tasks)
        return (int)$this->agentTasks()->sum('total_tokens') > 0;
    }

    /**
     * Получение токенов запроса с fallback на 0
     * Подсчитывает суммарный расход prompt токенов для всех корневых задач и их дочерних задач
     *
     * @return int
     */
    public function getPromptTokensOrZero(): int
    {
        // Получаем только корневые задачи (без parent_id)
        $rootTasks = $this->agentTasks()->whereNull('parent_id')->get();

        $totalTokens = 0;

        foreach ($rootTasks as $rootTask) {
            // Добавляем токены самой корневой задачи
            $totalTokens += $rootTask->getPromptTokensOrZero();

            // Добавляем токены всех дочерних задач рекурсивно
            $totalTokens += $this->getSubtasksPromptTokensRecursive($rootTask);
        }

        return $totalTokens;
    }

    /**
     * Получение токенов ответа с fallback на 0
     * Подсчитывает суммарный расход completion токенов для всех корневых задач и их дочерних задач
     *
     * @return int
     */
    public function getCompletionTokensOrZero(): int
    {
        // Получаем только корневые задачи (без parent_id)
        $rootTasks = $this->agentTasks()->whereNull('parent_id')->get();

        $totalTokens = 0;

        foreach ($rootTasks as $rootTask) {
            // Добавляем токены самой корневой задачи
            $totalTokens += $rootTask->getCompletionTokensOrZero();

            // Добавляем токены всех дочерних задач рекурсивно
            $totalTokens += $this->getSubtasksCompletionTokensRecursive($rootTask);
        }

        return $totalTokens;
    }

    /**
     * Получение общих токенов с fallback на 0
     * Подсчитывает суммарный расход токенов для всех корневых задач и их дочерних задач
     *
     * @return int
     */
    public function getTotalTokensOrZero(): int
    {
        // Получаем только корневые задачи (без parent_id)
        $rootTasks = $this->agentTasks()->whereNull('parent_id')->get();

        $totalTokens = 0;

        foreach ($rootTasks as $rootTask) {
            // Добавляем токены самой корневой задачи
            $totalTokens += $rootTask->getTotalTokensOrZero();

            // Добавляем токены всех дочерних задач рекурсивно
            $totalTokens += $this->getSubtasksTokensRecursive($rootTask);
        }

        return $totalTokens;
    }

    /**
     * Рекурсивный подсчет токенов для дочерних задач
     *
     * @param AgentTask $task
     * @return int
     */
    private function getSubtasksTokensRecursive(AgentTask $task): int
    {
        $subtasksTokens = 0;

        foreach ($task->children as $child) {
            // Добавляем токены дочерней задачи
            $subtasksTokens += $child->getTotalTokensOrZero();

            // Рекурсивно добавляем токены её дочерних задач
            $subtasksTokens += $this->getSubtasksTokensRecursive($child);
        }

        return $subtasksTokens;
    }

    /**
     * Рекурсивный подсчет prompt токенов для дочерних задач
     *
     * @param AgentTask $task
     * @return int
     */
    private function getSubtasksPromptTokensRecursive(AgentTask $task): int
    {
        $subtasksTokens = 0;

        foreach ($task->children as $child) {
            // Добавляем prompt токены дочерней задачи
            $subtasksTokens += $child->getPromptTokensOrZero();

            // Рекурсивно добавляем prompt токены её дочерних задач
            $subtasksTokens += $this->getSubtasksPromptTokensRecursive($child);
        }

        return $subtasksTokens;
    }

    /**
     * Рекурсивный подсчет completion токенов для дочерних задач
     *
     * @param AgentTask $task
     * @return int
     */
    private function getSubtasksCompletionTokensRecursive(AgentTask $task): int
    {
        $subtasksTokens = 0;

        foreach ($task->children as $child) {
            // Добавляем completion токены дочерней задачи
            $subtasksTokens += $child->getCompletionTokensOrZero();

            // Рекурсивно добавляем completion токены её дочерних задач
            $subtasksTokens += $this->getSubtasksCompletionTokensRecursive($child);
        }

        return $subtasksTokens;
    }

    /**
     * Получение токенов с fallback на 0 (legacy метод)
     * @return int
     * @deprecated Используйте getTotalTokensOrZero()
     *
     */
    public function getTokensOrZero(): int
    {
        return $this->getTotalTokensOrZero();
    }

    /**
     * Добавить новые методы для работы с агентами
     */

    /**
     * Проверить, есть ли сообщения в чате (новая реализация без ограничений)
     *
     * @return bool
     */
    public function hasMessages(): bool
    {
        return !empty($this->messages) && is_array($this->messages) && count($this->messages) > 0;
    }

    /**
     * Безопасно обновить сообщения чата (для агентов)
     *
     * @param array $messages Новые сообщения
     * @param array $tokenStats Статистика токенов для добавления
     * @return bool
     */
    public function updateMessages(array $messages, array $tokenStats = []): bool
    {
        // Токены больше не обновляются в llm_chats; они живут в agent_tasks
        return $this->update(['messages' => $messages]);
    }

    /**
     * Связанные задачи агента, относящиеся к этому чату
     */
    public function agentTasks(): HasMany
    {
        return $this->hasMany(AgentTask::class, 'chat_id');
    }

    /**
     * Получить количество сообщений в чате
     *
     * @return int
     */
    public function getMessagesCount(): int
    {
        return $this->hasMessages() ? count($this->messages) : 0;
    }

    /**
     * Получить последнее сообщение из чата
     *
     * @return array|null
     */
    public function getLastMessage(): ?array
    {
        if (!$this->hasMessages()) {
            return null;
        }

        return end($this->messages);
    }

    /**
     * Представление данных чата для API/вида
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'messages' => $this->messages ?? [],
            'context' => $this->context ?? [],
            'total_tokens' => $this->getTotalTokensOrZero(),
            'context_fill' => $this->context_fill,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Scope для поиска чатов с сообщениями
     */
    public function scopeWithMessages($query)
    {
        return $query->whereNotNull('messages')
            ->where('messages', '!=', '[]')
            ->where('messages', '!=', '');
    }

    /**
     * Scope для поиска пустых чатов
     */
    public function scopeEmpty($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('messages')
                ->orWhere('messages', '[]')
                ->orWhere('messages', '');
        });
    }
}
