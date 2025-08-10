<?php

namespace App\Services\TaskDescriptionGenerator;

class StubDescriptionGenerator implements TaskDescriptionGeneratorInterface
{
    /**
     * Generate task description based on version difference data
     * This is a stub implementation that proxies the data without generation
     *
     * @param array $differenceData Data about the difference between versions
     * @return string Generated task description
     */
    public function generateDescription(array $differenceData): string
    {
        // Stub implementation - just return a basic description
        return 'Task created from version difference: ' . json_encode($differenceData);
    }
}
