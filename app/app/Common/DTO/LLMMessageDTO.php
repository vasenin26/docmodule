<?php

namespace App\Common\DTO;

readonly class LLMMessageDTO
{
    public function __construct(
        public string $id,
        public string $role,
        public string $message,
    ) {}
}
