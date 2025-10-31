<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AgentTasksPanelResource extends JsonResource
{
    public function toArray($request)
    {
        $task = $this->resource;

        // Формируем минимальный набор полей, используемых панелью
        return [
            'id' => (string) $task->id,
            'chat_id' => $task->chat_id,
            // agent_task_id — оставляем для совместимости с фронтом
            'agent_task_id' => $task->id,
            // Ссылка на задачу (может быть null если нет страницы)
            'url' => isset($task->id) && function_exists('route') ? route('agent-tasks.chat-content', ['id' => $task->id]) : null,
            'raw_status' => $task->status,
            'updated_at' => $task->updated_at?->toDateTimeString(),
        ];
    }
}
