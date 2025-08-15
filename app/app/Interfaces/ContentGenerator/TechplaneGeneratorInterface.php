<?php

namespace App\Interfaces\ContentGenerator;

use App\Common\DTO\LLMGenerationResult;

interface TechplaneGeneratorInterface
{
    /**
     * Generate techplan based on task description and attached files
     *
     * @param string $taskDescription Task description for which to generate techplan
     * @param array $attachedFiles List of attached files from the page
     * @return LLMGenerationResult Generated techplan
     */
    public function generate(string $taskDescription, array $attachedFiles = []): LLMGenerationResult;
}
