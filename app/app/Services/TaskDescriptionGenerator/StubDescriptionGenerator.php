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
        $description = 'Task created from version difference.';
        
        // Include diff_output if available
        if (isset($differenceData['diff_output']) && !empty($differenceData['diff_output'])) {
            $description .= "\n\nChanges:\n" . $differenceData['diff_output'];
        }
        
        // Include basic information for backward compatibility
        if (isset($differenceData['new_version_title'])) {
            $description .= "\n\nPage: " . $differenceData['new_version_title'];
        }
        
        if (isset($differenceData['is_new_page']) && $differenceData['is_new_page']) {
            $description .= "\nType: New page created";
        } else {
            $description .= "\nType: Page updated";
        }
        
        return $description;
    }
}
