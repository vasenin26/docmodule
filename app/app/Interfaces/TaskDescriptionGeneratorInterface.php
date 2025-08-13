<?php

namespace App\Interfaces;

use App\Common\DTO\DifferenceDataDTO;
use App\Common\DTO\LLMResultDTO;

interface TaskDescriptionGeneratorInterface
{
    /**
     * Generate task description based on version difference data
     *
     * @param DifferenceDataDTO $differenceData Data about the difference between versions
     * @return string Generated task description
     */
    public function generateDescription(DifferenceDataDTO $differenceData): LLMResultDTO;
}
