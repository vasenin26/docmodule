<?php

namespace App\Interfaces\ContentGenerator;

use App\Common\DTO\DifferenceDataDTO;
use App\Common\DTO\LLMGenerationResult;
use App\Models\LLMChat;
use App\Models\Repository;

interface DiffDescriptionGeneratorInterface
{
    /**
     * Generate task description based on version difference data
     *
     * @param DifferenceDataDTO $differenceData Data about the difference between versions
     * @param Repository[] $repositories
     * @param string[] $attachedFiles
     * @return LLMGenerationResult Generated task description
     *
     */
    public function generate(DifferenceDataDTO $differenceData, array $repositories, array $attachedFiles): LLMGenerationResult;
}
