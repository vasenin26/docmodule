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
        'tokens',
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
     * Проверка был ли рассчитан размер токенов
     * 
     * @return bool
     */
    public function isTokensCalculated(): bool
    {
        return $this->tokens !== null;
    }

    /**
     * Получение токенов с fallback на 0
     * 
     * @return int
     */
    public function getTokensOrZero(): int
    {
        return $this->tokens ?? 0;
    }
}
