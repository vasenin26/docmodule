<?php

namespace App\Services\DiffGenerator;

interface DiffGeneratorInterface
{
    /**
     * Generate diff output in git diff format
     *
     * @param string $oldContent
     * @param string $newContent
     * @param string $type
     * @return string
     */
    public function generateDiff(string $oldContent, string $newContent, string $type = 'content'): string;
}
