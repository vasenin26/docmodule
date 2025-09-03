<?php

namespace App\Factory;

use App\Interfaces\Factory\AgentResultHandlerFactoryInterface;
use App\Interfaces\LLM\AgentResultHandlerInterface;
use App\Models\AgentTask;
use App\Services\AgentTaskManager\Handlers\VersionDiffResultHandler;
use Illuminate\Support\Facades\Log;

class AgentResultHandlerFactory implements AgentResultHandlerFactoryInterface
{
    public function createTaskHandler(AgentTask $task): ?AgentResultHandlerInterface
    {
        $handlerClass = $task->handler;
        $implementationClass = class_implements($handlerClass);

        if(in_array(AgentResultHandlerInterface::class, $implementationClass)) {
            try {
                return $handlerClass::createFromTask($task);
            } catch (\Exception $e) {
                Log::warning($e->getMessage());
                return null;
            }
        }

        Log::warning("Try to handle not AgentResultHandlerInterface class $handlerClass");

        return null;
    }

    public function createVersionDiffResultHandler($versionDiffTask): AgentResultHandlerInterface
    {
        return new VersionDiffResultHandler($versionDiffTask);
    }
}
