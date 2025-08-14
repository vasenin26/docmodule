<?php

namespace App\Services\TaskDescriptionGenerator;

use App\Interfaces\ContentGenerator\DiffDescriptionGeneratorInterface;
use App\Interfaces\Factory\TaskDescriptionGeneratorFactoryInterface;
use App\Interfaces\LLM\ContentGenerator;

class TaskDescriptionGeneratorFactory implements TaskDescriptionGeneratorFactoryInterface
{
    public function __construct(
        private ContentGenerator $contentGenerator
    )
    {
    }

    public function getProjectGenerator(int $projectId): DiffDescriptionGeneratorInterface
    {
        return new DiffDescriptionGenerator($this->contentGenerator);
    }

    public function getSimpleGenerator(): DiffDescriptionGeneratorInterface
    {
        return new DiffDescriptionGenerator($this->contentGenerator);
    }
}
