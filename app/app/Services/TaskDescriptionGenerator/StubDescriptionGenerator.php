<?php

namespace App\Services\TaskDescriptionGenerator;

use App\Common\DTO\DifferenceDataDTO;
use App\Interfaces\TaskDescriptionGeneratorInterface;

class StubDescriptionGenerator implements TaskDescriptionGeneratorInterface
{
    /**
     * Generate task description based on version difference data
     * This is a stub implementation that proxies the data without generation
     *
     * @param DifferenceDataDTO $differenceData Data about the difference between versions
     * @return string Generated task description
     */
    public function generateDescription(DifferenceDataDTO $differenceData): string
    {
        $description = 'Task created from version difference.';

        // Include diff_output if available
        if ($differenceData->diffOutput && !empty($differenceData->diffOutput)) {
            $description .= "\n\nChanges:\n" . $differenceData->diffOutput;
        }

        // Include basic information for backward compatibility
        if ($differenceData->newVersionTitle) {
            $description .= "\n\nPage: " . $differenceData->newVersionTitle;
        }

        if ($differenceData->isNewPage) {
            $description .= "\nType: New page created";
        } else {
            $description .= "\nType: Page updated";
        }

        return $description;
    }
}
