<?php

namespace App\Services\TaskDescriptionGenerator;

use App\Interfaces\ContentGenerator\TaskDescriptionGeneratorInterface;
use App\Interfaces\Factory\TaskDescriptionGeneratorFactoryInterface;
use App\Interfaces\LLM\LLMGenerator;

class TaskDescriptionGeneratorFactory implements TaskDescriptionGeneratorFactoryInterface
{
    public function __construct(
        private LLMGenerator $llmGenerator
    )
    {
    }

    public function getProjectGenerator(int $projectId): TaskDescriptionGeneratorInterface
    {
        return new LLMDescriptionGenerator($this->llmGenerator);
    }

    public function getSimpleGenerator(): TaskDescriptionGeneratorInterface
    {
        return new LLMDescriptionGenerator($this->llmGenerator);
    }
}
