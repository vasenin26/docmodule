<?php

namespace App\Factory;

use App\Interfaces\AgentFactoryInterface;
use App\Interfaces\LLMGenerator;
use App\Interfaces\TaskDescriptionGeneratorInterface;
use App\Services\LLMGenerator\ToolsFactory;
use App\Services\TaskDescriptionGenerator\LLMDescriptionGenerator;

class AgentFactory implements AgentFactoryInterface
{
    public function __construct(
        private LLMGenerator $llmGenerator
    )
    {
    }

    public function getDescriptionGenerator(?int $projectId): TaskDescriptionGeneratorInterface
    {
        return new LLMDescriptionGenerator($this->llmGenerator);
    }
}
