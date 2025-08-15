<?php

namespace App\Interfaces\Factory;

use App\Interfaces\ContentGenerator\TechplaneGeneratorInterface;

interface TechplaneGeneratorFactoryInterface
{
    public function getTechplaneGenerator(): TechplaneGeneratorInterface;
    public function getProjectTechplaneGenerator(int $projectId): TechplaneGeneratorInterface;
}
