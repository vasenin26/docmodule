<?php

namespace App\Interfaces\Factory;

use App\Interfaces\LLM\AgentResultHandlerInterface;
use App\Models\AgentTask;
use App\Models\LLMChat;
use App\Models\Techplane;
use App\Models\VersionDiffTask;
use App\Models\Actualization;
use App\Models\Implementation;
use App\Models\Terminal;

interface AgentResultHandlerFactoryInterface
{

    public function createTaskHandler(AgentTask $task): ?AgentResultHandlerInterface;

    public function createVersionDiffResultHandler(VersionDiffTask $versionDiffTask): AgentResultHandlerInterface;

    public function createTechplaneResultHandler(Techplane $techplane): AgentResultHandlerInterface;

    public function createActualizationResultHandler(Actualization $actualization): AgentResultHandlerInterface;

    public function createImplementationResultHandler(Implementation $implementation): AgentResultHandlerInterface;

    public function createTerminalResultHandler(Terminal $terminal): AgentResultHandlerInterface;

    public function createChatHandler(LLMChat $chat): AgentResultHandlerInterface;
}
