<?php

namespace App\Interfaces\ContentGenerator;

interface DiffGeneratorInterface
{
    /**
     * Generate diff output in git diff format
     *
     * @param string|null $oldContent
     * @param string|null $newContent
     * @param string $type
     * @return string
     */
    public function generateDiff(?string $oldContent, ?string $newContent, string $type = 'content'): string;
}
