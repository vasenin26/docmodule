<?php

namespace App\Interfaces;

use App\Common\DTO\LLMResultDTO;

interface LLMGenerator
{
    public function generate(string $prompt, string $systemPrompt = ''): LLMResultDTO;
}
