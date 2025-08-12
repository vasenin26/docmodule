<?php

namespace App\Services\LLMGenerator;

use App\Services\LLMGenerator\Tools\CurrentTime;
use App\Services\LLMGenerator\Tools\ToolInterface;

class ToolsFactory
{
    private array $meta = [];
    private array $map = [];

    public function __construct(
        CurrentTime $currentTime
    )
    {
        $this->register($currentTime, 'time');
    }

    public function register(ToolInterface $tool, string $name): void
    {
        $this->meta[$name] = $tool->getProps($name);
        $this->map[$name] = $tool;
    }

    public function getMeta(): array
    {
        return array_values($this->meta);
    }

    public function callTool(string $toolName, string $args): string
    {
        return $this->map[$toolName]->execute($args);
    }
}
