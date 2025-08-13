<?php

namespace App\Common\DTO;

readonly class LLMGenerationResult
{
    public function __construct(
        public string $result,
        public ?int $chatId,
    )
    {
    }
}
