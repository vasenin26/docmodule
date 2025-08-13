<?php

namespace App\Common\DTO;

readonly class LLMResultDTO
{
    public function __construct(
        public string $answer,
        public array $messages
    )
    {
    }
}
