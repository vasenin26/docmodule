<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LLMChat extends Model
{
    use HasFactory;

    const ROLE_SYSTEM = 'system';
    const ROLE_USER = 'user';


    protected $table = 'llm_chats';

    protected $fillable = [
        'messages',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
    ];

    protected function casts(): array
    {
        return [
            'messages' => 'array',
        ];
    }

    /**
     * Проверка был ли рассчитан размер токенов (любого типа)
     *
     * @return bool
     */
    public function isTokensCalculated(): bool
    {
        return $this->prompt_tokens !== null ||
            $this->completion_tokens !== null ||
            $this->total_tokens !== null;
    }

    /**
     * Получение токенов запроса с fallback на 0
     *
     * @return int
     */
    public function getPromptTokensOrZero(): int
    {
        return $this->prompt_tokens ?? 0;
    }

    /**
     * Получение токенов ответа с fallback на 0
     *
     * @return int
     */
    public function getCompletionTokensOrZero(): int
    {
        return $this->completion_tokens ?? 0;
    }

    /**
     * Получение общих токенов с fallback на 0
     *
     * @return int
     */
    public function getTotalTokensOrZero(): int
    {
        return $this->total_tokens ?? 0;
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
        $updateData = ['messages' => $messages];

        // Добавляем статистику токенов если предоставлена
        if (!empty($tokenStats)) {
            if (isset($tokenStats['prompt_tokens'])) {
                $updateData['prompt_tokens'] = $this->getPromptTokensOrZero() + $tokenStats['prompt_tokens'];
            }
            if (isset($tokenStats['completion_tokens'])) {
                $updateData['completion_tokens'] = $this->getCompletionTokensOrZero() + $tokenStats['completion_tokens'];
            }
            if (isset($tokenStats['total_tokens'])) {
                $updateData['total_tokens'] = $this->getTotalTokensOrZero() + $tokenStats['total_tokens'];
            }
        }

        return $this->update($updateData);
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

    public function addSystemMessage(string $content): void
    {
        $this->addMessage(self::ROLE_SYSTEM, $content);
    }

    public function addUserMessage(string $content): void
    {
        $this->addMessage(self::ROLE_USER, $content);
    }

    public function addMessage(string $role, string $content): void
    {
        $messages = [...$this->messages, [
            'role' => $role,
            'content' => $content,
        ]];

        $this->attributes['messages'] = json_encode($messages);
    }
}
