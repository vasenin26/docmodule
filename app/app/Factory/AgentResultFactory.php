<?php

namespace App\Factory;

use App\Interfaces\Factory\AgentResultHandlerFactoryInterface;
use App\Interfaces\LLM\AgentResultHandlerInterface;
use App\Models\AgentTask;
use App\Services\AgentTaskManager\Handlers\VersionDiffResultHandler;

class AgentResultFactory implements AgentResultHandlerFactoryInterface
{
    public function createTaskHandler(AgentTask $task): ?AgentResultHandlerInterface
    {
        return VersionDiffResultHandler::createFromTask($task);
    }

    public function createVersionDiffResultHandler($versionDiffTask): AgentResultHandlerInterface
    {
        return new VersionDiffResultHandler($versionDiffTask);
    }
}
