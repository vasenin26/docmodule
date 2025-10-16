<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AgentTaskResource extends JsonResource
{
    public function toArray($request)
    {
        $task = $this->resource;

        return [
            'id' => $task->id,
            'type' => $task->type?->value ?? $task->type,
            'handler' => $task->handler,
            'handler_options' => $task->handler_options,
            'project' => $task->project ? ['id' => $task->project->id, 'title' => $task->project->title] : null,
            'creator' => $task->creator ? ['id' => $task->creator->id, 'name' => $task->creator->name] : null,
            'chat_id' => $task->chat_id,
            'llm_chat' => $task->llmChat ? ['id' => $task->llmChat->id] : null,
            'status' => $task->status,
            'agent_id' => $task->agent_id,
            'agent_uuid' => $task->agent_uuid,
            'agent_model' => $task->agent_model,
            'result_required' => (bool) $task->result_required,
            'context_id' => $task->context_id,
            'timeout' => $task->timeout,
            'reserved_at' => $task->reserved_at?->toDateTimeString(),
            'reserved_until' => $task->reserved_until?->toDateTimeString(),
            'reserved_seconds' => $task->reserved_seconds,
            'created_at' => $task->created_at?->toDateTimeString(),
            'updated_at' => $task->updated_at?->toDateTimeString(),
        ];
    }
}


