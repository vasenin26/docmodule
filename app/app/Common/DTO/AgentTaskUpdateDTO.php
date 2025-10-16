<?php

namespace App\Common\DTO;

use App\Common\DTO\LLM\LLMResultDTO;
use Illuminate\Support\Facades\Request;

class AgentTaskUpdateDTO
{
    public function __construct(
        public array $chat,
        public LLMResultDTO $stats,
        public ?string $result = null,
        public ?float $context_fill = null,
        public ?string $model = null,
    ) {}

    /**
     * Создать DTO из массива данных (например, из HTTP запроса)
     *
     * @param array $data Массив с данными
     * @return self
     * @throws \InvalidArgumentException Если отсутствуют обязательные поля
     */
    public static function fromArray(array $data): self
    {
        // Валидация обязательных полей
        if (!isset($data['chat']) || !is_array($data['chat'])) {
            throw new \InvalidArgumentException('Поле chat обязательно и должно быть массивом');
        }

        if (!isset($data['stats']) || !is_array($data['stats'])) {
            throw new \InvalidArgumentException('Поле stats обязательно и должно быть массивом');
        }

        // Создание LLMResultDTO из статистики
        $stats = new LLMResultDTO(
            answer: '', // Не используется в этом контексте
            messages: [], // Не используется в этом контексте
            prompt_tokens: $data['stats']['prompt_tokens'] ?? null,
            completion_tokens: $data['stats']['completion_tokens'] ?? null,
            total_tokens: $data['stats']['total_tokens'] ?? null
        );

        return new self(
            chat: $data['chat'],
            stats: $stats,
            result: $data['result'] ?? null,
            context_fill: self::clamp(isset($data['context_fill']) ? (is_numeric($data['context_fill']) ? (float)$data['context_fill'] : null) : null),
            model: $data['model'] ?? null,
        );
    }

    /**
     * Проверить, является ли это финальным обновлением (с результатом)
     *
     * @return bool
     */
    public function isFinal(): bool
    {
        return $this->result !== null;
    }

    /**
     * Проверить валидность данных чата
     *
     * @return bool
     */
    public function hasValidChat(): bool
    {
        if (empty($this->chat)) {
            return false;
        }

        // Проверяем, что каждое сообщение имеет обязательные поля
        foreach ($this->chat as $message) {
            if (!is_array($message) ||
                !isset($message['role']) ||
                !isset($message['content'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Получить статистику токенов в виде массива
     *
     * @return array
     */
    public function getTokenStats(): array
    {
        return [
            'prompt_tokens' => $this->stats->prompt_tokens,
            'completion_tokens' => $this->stats->completion_tokens,
            'total_tokens' => $this->stats->total_tokens,
        ];
    }

    /**
     * Преобразовать в массив для логирования или отладки
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'chat_messages_count' => count($this->chat),
            'stats' => $this->getTokenStats(),
            'has_result' => $this->isFinal(),
            'result_length' => $this->result ? strlen($this->result) : 0,
            'context_fill' => $this->context_fill,
            'model' => $this->model,
        ];
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function hasContextFill(): bool
    {
        return $this->context_fill !== null;
    }

    private static function clamp(?float $value): ?float
    {
        if ($value === null) {
            return null;
        }
        if ($value < 0.0) {
            return 0.0;
        }
        if ($value > 1.0) {
            return 1.0;
        }
        return $value;
    }
}
