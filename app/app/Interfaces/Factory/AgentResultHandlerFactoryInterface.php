<?php

namespace App\Interfaces\Factory;

use App\Interfaces\LLM\AgentResultHandlerInterface;
use App\Models\AgentTask;

interface AgentResultHandlerFactoryInterface
{

    public function createTaskHandler(AgentTask $task): ?AgentResultHandlerInterface;

    public function createVersionDiffResultHandler($versionDiffTask): AgentResultHandlerInterface;
}
