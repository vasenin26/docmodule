<?php

namespace App\Interfaces\Factory;

use App\Interfaces\ContentGenerator\ActualizationGeneratorInterface;
use App\Interfaces\ContentGenerator\DiffDescriptionGeneratorInterface;
use App\Interfaces\ContentGenerator\TechplaneGeneratorInterface;

interface AgentFactoryInterface
{
    public  function getDescriptionGenerator(?int $projectId): DiffDescriptionGeneratorInterface;
    
    public function getTechplaneGenerator(?int $projectId): TechplaneGeneratorInterface;
    
    public function getActualizationGenerator(int $projectId): ActualizationGeneratorInterface;
}
