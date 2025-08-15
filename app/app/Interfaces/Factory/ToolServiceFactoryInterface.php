<?php

namespace App\Interfaces\Factory;

use App\Interfaces\LLM\LLMTools;

interface ToolServiceFactoryInterface
{
    public function withAllTools(): LLMTools;
    public function withAllToolsForProject(int $projectId): LLMTools;
}
