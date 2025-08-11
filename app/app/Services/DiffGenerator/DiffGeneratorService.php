<?php

namespace App\Services\DiffGenerator;

use App\Interfaces\DiffGeneratorInterface;

class DiffGeneratorService implements DiffGeneratorInterface
{
    /**
     * Generate diff output in git diff format
     *
     * @param string $oldContent
     * @param string $newContent
     * @param string $type
     * @return string
     */
    public function generateDiff(string $oldContent, string $newContent, string $type = 'content'): string
    {
        $oldLines = explode("\n", $oldContent);
        $newLines = explode("\n", $newContent);

        $diffOutput = [];

        // Handle title changes
        if ($type === 'title') {
            if ($oldContent !== $newContent) {
                if (!empty($oldContent)) {
                    $diffOutput[] = "- {$oldContent}";
                }
                if (!empty($newContent)) {
                    $diffOutput[] = "+ {$newContent}";
                }
            }
            return implode("\n", $diffOutput);
        }

        // Handle content changes
        $oldLines = array_filter($oldLines, function($line) { return $line !== ''; });
        $newLines = array_filter($newLines, function($line) { return $line !== ''; });

        $deletedLines = array_diff($oldLines, $newLines);
        $addedLines = array_diff($newLines, $oldLines);

        foreach ($deletedLines as $line) {
            $diffOutput[] = "- {$line}";
        }

        foreach ($addedLines as $line) {
            $diffOutput[] = "+ {$line}";
        }

        return implode("\n", $diffOutput);
    }
}
