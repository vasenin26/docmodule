<?php

namespace App\Interfaces\LLM;

use App\Models\AgentTask;

interface AgentResultHandlerInterface
{
    public function getOptions(): array;
    public function handleResult(string $result): void;
    public static function createFromTask(AgentTask $task): static;
}
