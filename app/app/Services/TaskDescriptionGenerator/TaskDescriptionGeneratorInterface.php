<?php

namespace App\Services\TaskDescriptionGenerator;

interface TaskDescriptionGeneratorInterface
{
    /**
     * Generate task description based on version difference data
     *
     * @param array $differenceData Data about the difference between versions
     * @return string Generated task description
     */
    public function generateDescription(array $differenceData): string;
}
