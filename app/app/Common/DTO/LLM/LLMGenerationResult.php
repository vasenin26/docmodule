<?php

namespace App\Common\DTO\LLM;

readonly class LLMGenerationResult
{
    public function __construct(
        public string $result,
        public ?int $chatId,
    )
    {
    }
}
