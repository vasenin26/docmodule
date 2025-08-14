<?php

namespace App\Interfaces\LLM;

use App\Common\DTO\LLMResultDTO;

interface ContentGenerator
{
    public function generate(string $prompt, string $systemPrompt = ''): LLMResultDTO;

    public function processMessages(array $messages);
}
