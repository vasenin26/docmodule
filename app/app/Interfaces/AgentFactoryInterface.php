<?php

namespace App\Interfaces;

interface AgentFactoryInterface
{
    public  function getDescriptionGenerator(int $projectId): TaskDescriptionGeneratorInterface;
}
