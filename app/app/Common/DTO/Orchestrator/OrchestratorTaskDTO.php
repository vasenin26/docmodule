<?php

namespace App\Common\DTO\Orchestrator;

use App\Models\AgentTask;

readonly class OrchestratorTaskDTO
{
    public function __construct(
        public string $id,
        public string $project_id,
        public ?string $context_id = null,
        public ?int $timeout = null,
        public ?string $public_key = null,
    ) {}
    
    /**
     * Создать DTO из модели AgentTask
     */
    public static function fromAgentTask(AgentTask $task): self
    {
        return new self(
            id: (string) $task->id,
            project_id: (string) $task->project_id,
            context_id: $task->context_id,
            timeout: $task->timeout,
            public_key: $task->project->public_key ?? null,
        );
    }
    
    /**
     * Преобразовать в массив для JSON ответа
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'context_id' => $this->context_id,
            'timeout' => $this->timeout,
            'public_key' => $this->public_key,
        ];
    }
}

