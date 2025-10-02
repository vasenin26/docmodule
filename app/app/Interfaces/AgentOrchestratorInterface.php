<?php

namespace App\Interfaces;

use App\Common\DTO\RemoteAgent\AgentMeta;
use App\Common\DTO\RemoteAgent\ConfigOptions;

interface AgentOrchestratorInterface
{
    public function startAgent(ConfigOptions $configOptions): AgentMeta;
    public function stopAgent(AgentMeta $meta): AgentMeta;
    public function startProcess(string $taskType): void;
}
