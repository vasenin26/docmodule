<?php

namespace App\Common\DTO\LLM;

readonly class LLMMessageDTO
{
    public function __construct(
        public string $id,
        public string $role,
        public string $message,
    ) {}
}
