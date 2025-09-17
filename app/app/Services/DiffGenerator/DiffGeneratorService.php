<?php

namespace App\Services\DiffGenerator;

use App\Common\DTO\DifferenceDataDTO;
use App\Interfaces\ContentGenerator\DiffGeneratorInterface;

class DiffGeneratorService implements DiffGeneratorInterface
{
    public function createDifferenceDataDTO($currentVersion): DifferenceDataDTO
    {
        $previousVersion = $currentVersion->previousVersion;

        if (!$previousVersion) {
            // Новая страница
            return new DifferenceDataDTO(
                diffOutput: null,
                newVersionTitle: $currentVersion->title,
                isNewPage: true,
                titleChanged: false,
                contentChanged: false,
                newVersionId: $currentVersion->id,
                newVersionContent: $currentVersion->content,
                previousVersionId: null,
                previousVersionTitle: null,
                previousVersionContent: null
            );
        }

        // Обновленная страница
        $titleChanged = $currentVersion->title !== $previousVersion->title;
        $contentChanged = $currentVersion->content !== $previousVersion->content;

        // Генерируем diff если есть изменения
        $diffOutput = null;
        if ($titleChanged || $contentChanged) {
            $diffOutput = $this->generateDiff($previousVersion->content, $currentVersion->content);
        }

        return new DifferenceDataDTO(
            diffOutput: $diffOutput,
            newVersionTitle: $currentVersion->title,
            isNewPage: false,
            titleChanged: $titleChanged,
            contentChanged: $contentChanged,
            newVersionId: $currentVersion->id,
            newVersionContent: $currentVersion->content,
            previousVersionId: $previousVersion->id,
            previousVersionTitle: $previousVersion->title,
            previousVersionContent: $previousVersion->content
        );
    }


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

        $diffOutput = implode("\n", $diffOutput);

        return $diffOutput;
    }




    /**
     * Принимает unified diff строкой,
     * возвращает "очищенный" diff (строкой),
     * где одинаковые -/+ схлопнуты в контекст,
     * но только если порядок строк совпадает.
     */
    private function collapseMovedLines(string $diff): string
    {
        $lines = explode("\n", $diff);
        $result = [];
        $removed = [];
        $added = [];

        $flush = function () use (&$removed, &$added, &$result) {
            // ищем LCS между removed и added
            $i = 0;
            $j = 0;
            while ($i < count($removed) && $j < count($added)) {
                $r = substr($removed[$i], 1);
                $a = substr($added[$j], 1);

                if ($r === $a) {
                    // строки совпадают и порядок сохраняется → схлопываем
                    $result[] = ' ' . $r;
                    $i++;
                    $j++;
                } else {
                    // не совпали → выводим "как есть"
                    $result[] = $removed[$i];
                    $i++;
                }
            }
            // остатки
            while ($i < count($removed)) {
                $result[] = $removed[$i++];
            }
            while ($j < count($added)) {
                $result[] = $added[$j++];
            }

            $removed = [];
            $added = [];
        };

        foreach ($lines as $line) {
            if ($line === '') continue;

            if ($line[0] === '-') {
                $removed[] = $line;
            } elseif ($line[0] === '+') {
                $added[] = $line;
            } else {
                if ($removed || $added) {
                    $flush();
                }
                $result[] = $line;
            }
        }
        if ($removed || $added) {
            $flush();
        }

        return implode("\n", $result);
    }
}
