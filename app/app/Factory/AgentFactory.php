<?php

namespace App\Factory;

use App\Interfaces\ContentGenerator\ActualizationGeneratorInterface;
use App\Interfaces\ContentGenerator\DiffDescriptionGeneratorInterface;
use App\Interfaces\ContentGenerator\TechplaneGeneratorInterface;
use App\Interfaces\Factory\AgentFactoryInterface;
use App\Interfaces\Factory\TaskDescriptionGeneratorFactoryInterface;
use App\Interfaces\Factory\TechplaneGeneratorFactoryInterface;
use App\Interfaces\LLM\ContentGenerator;
use App\Models\Project;
use App\Services\RepositoryService\RepositoryProvider;
use App\Services\TaskDescriptionGenerator\ActualizationGenerator;
use Illuminate\Support\Facades\Log;

class AgentFactory implements AgentFactoryInterface
{
    public function __construct(
        private readonly TaskDescriptionGeneratorFactoryInterface $descriptionGeneratorFactory,
        private readonly TechplaneGeneratorFactoryInterface $techplaneGeneratorFactory,
        private readonly ContentGenerator $contentGenerator,
        private readonly RepositoryProvider $repositoryProvider,
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

    public function getActualizationGenerator(int $projectId): ActualizationGeneratorInterface
    {
        $project = Project::findOrFail($projectId);
        $repos = $project->repositories->all();
        
        return new ActualizationGenerator(
            $this->contentGenerator,
            $this->repositoryProvider,
            $repos
        );
    }
}
