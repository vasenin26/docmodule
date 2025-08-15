<?php

namespace App\Services\TaskDescriptionGenerator;

use App\Interfaces\ContentGenerator\TechplaneGeneratorInterface;
use App\Interfaces\Factory\TechplaneGeneratorFactoryInterface;
use App\Interfaces\LLM\ContentGenerator;
use App\Models\Project;

class TechplaneGeneratorFactory implements TechplaneGeneratorFactoryInterface
{
    public function __construct(
        private ContentGenerator $contentGenerator
    )
    {
    }

    public function getTechplaneGenerator(): TechplaneGeneratorInterface
    {
        return new TechplaneGenerator($this->contentGenerator);
    }

    public function getProjectTechplaneGenerator(int $projectId): TechplaneGeneratorInterface
    {
        $project = Project::findOrFail($projectId);
        $repos = $project->repositories->all();

        return new TechplaneGenerator($this->contentGenerator, $repos);
    }
}
