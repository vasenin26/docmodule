<?php

namespace App\Interfaces\Factory;

use App\Interfaces\ContentGenerator\TaskDescriptionGeneratorInterface;

interface TaskDescriptionGeneratorFactoryInterface
{
    public function getProjectGenerator(int $projectId): TaskDescriptionGeneratorInterface;
    public function getSimpleGenerator(): TaskDescriptionGeneratorInterface;
}
