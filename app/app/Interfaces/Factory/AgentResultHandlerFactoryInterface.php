<?php

namespace App\Interfaces\Factory;

use App\Interfaces\LLM\AgentResultHandlerInterface;
use App\Models\AgentTask;
use App\Models\Techplane;
use App\Models\VersionDiffTask;
use App\Models\Actualization;
use App\Models\Implementation;

interface AgentResultHandlerFactoryInterface
{

    public function createTaskHandler(AgentTask $task): ?AgentResultHandlerInterface;

    public function createVersionDiffResultHandler(VersionDiffTask $versionDiffTask): AgentResultHandlerInterface;
    
    public function createTechplaneResultHandler(Techplane $techplane): AgentResultHandlerInterface;

    public function createActualizationResultHandler(Actualization $actualization): AgentResultHandlerInterface;

    public function createImplementationResultHandler(Implementation $implementation): AgentResultHandlerInterface;
}
