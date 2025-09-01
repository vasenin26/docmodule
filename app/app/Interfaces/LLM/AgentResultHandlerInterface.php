<?php

namespace App\Interfaces\LLM;

use App\Models\AgentTask;

interface AgentResultHandlerInterface
{
    public static function getKey(): string;
    public function getOptions(): string;
    public function handleResult(string $result): void;
    public static function createFromTask(AgentTask $task): static;
}
