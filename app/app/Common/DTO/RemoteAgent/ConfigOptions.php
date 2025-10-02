<?php

namespace App\Common\DTO\RemoteAgent;

use Ramsey\Uuid\UuidInterface;

final readonly class ConfigOptions
{
    public function __construct(
        public UuidInterface $agentId,
        public string        $token,
    )
    {
    }
}
