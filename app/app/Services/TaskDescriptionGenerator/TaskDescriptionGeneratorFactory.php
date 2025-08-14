<?php

namespace App\Services\TaskDescriptionGenerator;

use App\Interfaces\ContentGenerator\DiffDescriptionGeneratorInterface;
use App\Interfaces\Factory\TaskDescriptionGeneratorFactoryInterface;
use App\Interfaces\LLM\ContentGenerator;
use App\Models\Project;
use Illuminate\Support\Facades\Log;

class TaskDescriptionGeneratorFactory implements TaskDescriptionGeneratorFactoryInterface
{
    public function __construct(
        private ContentGenerator $contentGenerator
    )
    {
    }

    public function getProjectGenerator(int $projectId): DiffDescriptionGeneratorInterface
    {
        $project = Project::findOrFail($projectId);
        $repos = $project->repositories->all();

        return new DiffDescriptionGenerator($this->contentGenerator, $repos);
    }

    public function getSimpleGenerator(): DiffDescriptionGeneratorInterface
    {
        return new DiffDescriptionGenerator($this->contentGenerator);
    }
}
