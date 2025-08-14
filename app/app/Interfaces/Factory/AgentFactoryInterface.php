<?php

namespace App\Interfaces\Factory;

use App\Interfaces\ContentGenerator\TaskDescriptionGeneratorInterface;

interface AgentFactoryInterface
{
    public  function getDescriptionGenerator(?int $projectId): TaskDescriptionGeneratorInterface;
}
