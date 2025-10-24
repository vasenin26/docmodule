<?php

namespace App\Http\Resources;

use App\Common\Enums\AgentTaskStatus;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentTaskResource extends JsonResource
{
    public function toArray($request)
    {
        $task = $this->resource;

        return [
            'id' => $task->id,
            'type' => $task->type?->value ?? $task->type,

            // creator: id + name (null-safe)
            'creator' => $task->creator ? ['id' => $task->creator->id, 'name' => $task->creator->name] : null,

            'chat_id' => $task->chat_id,
            'context_id' => $task->context_id,

            // model used by UI
            'agent_model' => $task->agent_model,

            // virtual boolean field — true only if both are not null
            'agent_assigned' => ($task->agent_id !== null && $task->agent_uuid !== null),

            // virtual boolean field — true if handler exists and can be instantiated
            'has_handler' => !empty($task->handler),

            // reservation info
            'reserved_at' => $task->reserved_at?->toDateTimeString(),
            'reserved_until' => $task->reserved_until?->toDateTimeString(),
            'reserved_seconds' => $task->reserved_seconds,

            'status' => $task->status,
            'status_description' => $this->getStatusDescription($task->status),
            'status_is_finished' => $this->isStatusFinished($task->status),
            'status_is_active' => $this->isStatusActive($task->status),

            // Token usage
            'prompt_tokens' => $task->prompt_tokens,
            'completion_tokens' => $task->completion_tokens,
            'total_tokens' => $task->total_tokens,

            // Стоимость (если задана) — переводим из целого RUB*1000 в рубли с плавающей точкой или null
            'cost' => is_null($task->cost) ? null : (int) $task->cost,

            // Last update
            'updated_at' => $task->updated_at?->toDateTimeString(),
        ];
    }

    /**
     * Получить описание статуса
     */
    private function getStatusDescription(string $status): string
    {
        $statusEnum = AgentTaskStatus::fromValue($status);
        return $statusEnum ? $statusEnum->getDescription() : $status;
    }

    /**
     * Проверить, является ли статус завершенным
     */
    private function isStatusFinished(string $status): bool
    {
        $statusEnum = AgentTaskStatus::fromValue($status);
        return $statusEnum ? $statusEnum->isFinished() : false;
    }

    /**
     * Проверить, является ли статус активным
     */
    private function isStatusActive(string $status): bool
    {
        $statusEnum = AgentTaskStatus::fromValue($status);
        return $statusEnum ? $statusEnum->isActive() : false;
    }
}
