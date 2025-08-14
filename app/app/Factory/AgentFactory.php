<?php

namespace App\Factory;

use App\Interfaces\ContentGenerator\TaskDescriptionGeneratorInterface;
use App\Interfaces\Factory\AgentFactoryInterface;
use App\Interfaces\Factory\TaskDescriptionGeneratorFactoryInterface;

class AgentFactory implements AgentFactoryInterface
{
    public function __construct(
        private readonly TaskDescriptionGeneratorFactoryInterface $descriptionGeneratorFactory,
    )
    {
    }

    public function getDescriptionGenerator(?int $projectId): TaskDescriptionGeneratorInterface
    {
        if(is_null($projectId)) {
            return $this->descriptionGeneratorFactory->getSimpleGenerator();
        } else {
            return $this->descriptionGeneratorFactory->getProjectGenerator($projectId);
        }
    }
}
