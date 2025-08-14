<?php

namespace App\Interfaces\Factory;

use App\Interfaces\ContentGenerator\DiffDescriptionGeneratorInterface;

interface AgentFactoryInterface
{
    public  function getDescriptionGenerator(?int $projectId): DiffDescriptionGeneratorInterface;
}
