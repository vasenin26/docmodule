<?php

namespace App\Interfaces\Factory;

use App\Interfaces\ContentGenerator\DiffDescriptionGeneratorInterface;

interface TaskDescriptionGeneratorFactoryInterface
{
    public function getProjectGenerator(int $projectId): DiffDescriptionGeneratorInterface;
    public function getSimpleGenerator(): DiffDescriptionGeneratorInterface;
}
