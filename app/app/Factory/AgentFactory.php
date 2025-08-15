<?php

namespace App\Factory;

use App\Interfaces\ContentGenerator\DiffDescriptionGeneratorInterface;
use App\Interfaces\ContentGenerator\TechplaneGeneratorInterface;
use App\Interfaces\Factory\AgentFactoryInterface;
use App\Interfaces\Factory\TaskDescriptionGeneratorFactoryInterface;
use App\Interfaces\Factory\TechplaneGeneratorFactoryInterface;
use Illuminate\Support\Facades\Log;

class AgentFactory implements AgentFactoryInterface
{
    public function __construct(
        private readonly TaskDescriptionGeneratorFactoryInterface $descriptionGeneratorFactory,
        private readonly TechplaneGeneratorFactoryInterface $techplaneGeneratorFactory,
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

    public function getTechplaneGenerator(?int $projectId): TechplaneGeneratorInterface
    {
        if(is_null($projectId)) {
            return $this->techplaneGeneratorFactory->getTechplaneGenerator();
        } else {
            return $this->techplaneGeneratorFactory->getProjectTechplaneGenerator($projectId);
        }
    }
}
