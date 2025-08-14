<?php

namespace App\Factory;

use App\Interfaces\ContentGenerator\DiffDescriptionGeneratorInterface;
use App\Interfaces\Factory\AgentFactoryInterface;
use App\Interfaces\Factory\TaskDescriptionGeneratorFactoryInterface;
use Illuminate\Support\Facades\Log;

class AgentFactory implements AgentFactoryInterface
{
    public function __construct(
        private readonly TaskDescriptionGeneratorFactoryInterface $descriptionGeneratorFactory,
    )
    {
    }

    public function getDescriptionGenerator(?int $projectId): DiffDescriptionGeneratorInterface
    {
        if(is_null($projectId)) {
            return $this->descriptionGeneratorFactory->getSimpleGenerator();
        } else {
            return $this->descriptionGeneratorFactory->getProjectGenerator($projectId);
        }
    }
}
