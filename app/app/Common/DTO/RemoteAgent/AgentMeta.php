<?php

namespace App\Common\DTO\RemoteAgent;

readonly final class AgentMeta
{
    public function __construct(
        public string $server,
        public string $agentId,
        public string $publicKey,
    )
    {
    }
}
