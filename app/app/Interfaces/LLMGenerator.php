<?php

namespace App\Interfaces;

interface LLMGenerator
{
    public function generate(string $prompt): string;
}
