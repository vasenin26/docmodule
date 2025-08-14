<?php

namespace App\Interfaces\ContentGenerator;

use App\Common\DTO\DifferenceDataDTO;
use App\Common\DTO\LLMGenerationResult;

interface DiffDescriptionGeneratorInterface
{
    /**
     * Generate task description based on version difference data
     *
     * @param DifferenceDataDTO $differenceData Data about the difference between versions
     * @return string Generated task description
     */
    public function generate(DifferenceDataDTO $differenceData): LLMGenerationResult;
}
