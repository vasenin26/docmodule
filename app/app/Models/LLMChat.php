<?php

namespace App\Models;

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
        'context_fill',
    ];

    protected function casts(): array
    {
        return [
            'messages' => 'array',
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
        return (int) $this->agentTasks()->sum('total_tokens') > 0;
    }

    /**
     * Получение токенов запроса с fallback на 0
     *
     * @return int
     */
    public function getPromptTokensOrZero(): int
    {
        return (int) $this->agentTasks()->sum('prompt_tokens');
    }

    /**
     * Получение токенов ответа с fallback на 0
     *
     * @return int
     */
    public function getCompletionTokensOrZero(): int
    {
        return (int) $this->agentTasks()->sum('completion_tokens');
    }

    /**
     * Получение общих токенов с fallback на 0
     *
     * @return int
     */
    public function getTotalTokensOrZero(): int
    {
        return (int) $this->agentTasks()->sum('total_tokens');
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
