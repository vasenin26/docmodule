<?php

namespace App\Interfaces\LLM;

interface AgentResultHandlerInterface
{
    public function getKey(): string;
    public function getOptions(): string;
    public function handleResult(string $result);
}
