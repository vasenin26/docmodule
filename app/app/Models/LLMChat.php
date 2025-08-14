<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LLMChat extends Model
{
    use HasFactory;

    protected $table = 'llm_chats';

    protected $fillable = [
        'messages',
        'prompt_tokens',
        'completion_tokens', 
        'total_tokens',
    ];

    protected $casts = [
        'messages' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Переопределяем update для защиты исторических данных
     * При попытке изменения непустого поля messages выбрасывается исключение
     */
    public function update(array $attributes = [], array $options = [])
    {
        // Проверяем, есть ли попытка изменить messages, если они уже сохранены
        if (isset($attributes['messages']) && $this->hasMessages()) {
            throw new \Exception('Изменение сохраненной истории переписки запрещено для сохранения исторических данных');
        }

        return parent::update($attributes, $options);
    }

    /**
     * Проверка наличия сообщений в чате
     * 
     * @return bool
     */
    public function hasMessages(): bool
    {
        return !empty($this->messages) && is_array($this->messages) && count($this->messages) > 0;
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
     * @deprecated Используйте getTotalTokensOrZero()
     * 
     * @return int
     */
    public function getTokensOrZero(): int
    {
        return $this->getTotalTokensOrZero();
    }
}
