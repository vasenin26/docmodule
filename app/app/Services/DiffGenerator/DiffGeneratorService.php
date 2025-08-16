<?php

namespace App\Services\DiffGenerator;

use App\Interfaces\ContentGenerator\DiffGeneratorInterface;

class DiffGeneratorService implements DiffGeneratorInterface
{
    /**
     * Generate diff output in git diff format
     *
     * @param string|null $oldContent
     * @param string|null $newContent
     * @param string $type
     * @return string
     */
    public function generateDiff(?string $oldContent, ?string $newContent, string $type = 'content'): string
    {
        // Обеспечиваем, что параметры являются строками
        $oldContentStr = $oldContent ?? '';
        $newContentStr = $newContent ?? '';
        
        $oldLines = explode("\n", $oldContentStr);
        $newLines = explode("\n", $newContentStr);

        $diffOutput = [];

        // Handle title changes
        if ($type === 'title') {
            if ($oldContentStr !== $newContentStr) {
                if (!empty($oldContentStr)) {
                    $diffOutput[] = "- {$oldContentStr}";
                }
                if (!empty($newContentStr)) {
                    $diffOutput[] = "+ {$newContentStr}";
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
