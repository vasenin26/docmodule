<?php

namespace App\Interfaces;

use App\Interfaces\LLM\AgentResultHandlerInterface;

interface AgentTaskManagerInterface
{
    public function createTask(
        AgentResultHandlerInterface $handler,
        int                         $projectId,
        int                         $chatId
    ): int;
}
