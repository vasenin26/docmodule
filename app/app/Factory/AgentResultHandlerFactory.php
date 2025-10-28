<?php

namespace App\Factory;

use App\Interfaces\Factory\AgentResultHandlerFactoryInterface;
use App\Interfaces\HtmlToMdInterface;
use App\Interfaces\LLM\AgentResultHandlerInterface;
use App\Models\AgentTask;
use App\Models\Actualization;
use App\Models\Implementation;
use App\Models\LLMChat;
use App\Models\Techplane;
use App\Services\AgentTaskManager\Handlers\ActualizationResultHandler;
use App\Services\AgentTaskManager\Handlers\ImplementationResultHandler;
use App\Services\AgentTaskManager\Handlers\TechplaneResultHandler;
use App\Services\AgentTaskManager\Handlers\VersionDiffResultHandler;
use Illuminate\Support\Facades\Log;

class AgentResultHandlerFactory implements AgentResultHandlerFactoryInterface
{

    public function __construct(
        private HtmlToMdInterface $converter
    )
    {
    }

    public function createTaskHandler(AgentTask $task): ?AgentResultHandlerInterface
    {
        $handlerClass = $task->handler;

        if (empty($handlerClass)) {
            return null;
        }

        try {
            $implementationClass = class_implements($handlerClass);
        } catch (\Exception $exception) {
            return null;
        }

        if (in_array(AgentResultHandlerInterface::class, $implementationClass)) {
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

    public function createTechplaneResultHandler(Techplane $techplane): AgentResultHandlerInterface
    {
        return new TechplaneResultHandler($techplane);
    }

    public function createActualizationResultHandler(Actualization $actualization): AgentResultHandlerInterface
    {
        return new ActualizationResultHandler($actualization, $this->converter);
    }

    public function createImplementationResultHandler(Implementation $implementation): AgentResultHandlerInterface
    {
        return new ImplementationResultHandler($implementation);
    }

    public function createChatHandler(LLMChat $chat): AgentResultHandlerInterface
    {
        $lastTask = AgentTask::where(['chat_id' => $chat->id])->latest()->first();

        if (empty($lastTask)) {
            throw new \Exception('Chat have no task');
        }

        return $this->createTaskHandler($lastTask);
    }
}
