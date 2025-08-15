<?php

namespace App\Interfaces\ContentGenerator;

use App\Common\DTO\LLMGenerationResult;

interface TechplaneGeneratorInterface
{
    public function generate(string $taskDescription): LLMGenerationResult;
}
