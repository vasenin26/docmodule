<?php

namespace App\DTOs;

class DifferenceDataDTO
{
    public function __construct(
        public readonly ?string $diffOutput = null,
        public readonly ?string $newVersionTitle = null,
        public readonly bool $isNewPage = false,
        public readonly array $addedLines = [],
        public readonly array $removedLines = [],
        public readonly bool $titleChanged = false,
        public readonly bool $contentChanged = false
    ) {}

    /**
     * Create DTO from array data (for backward compatibility)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            diffOutput: $data['diff_output'] ?? null,
            newVersionTitle: $data['new_version_title'] ?? null,
            isNewPage: $data['is_new_page'] ?? false,
            addedLines: $data['added_lines'] ?? [],
            removedLines: $data['removed_lines'] ?? [],
            titleChanged: $data['title_changed'] ?? false,
            contentChanged: $data['content_changed'] ?? false
        );
    }

    /**
     * Convert DTO back to array (for backward compatibility)
     */
    public function toArray(): array
    {
        return [
            'diff_output' => $this->diffOutput,
            'new_version_title' => $this->newVersionTitle,
            'is_new_page' => $this->isNewPage,
            'added_lines' => $this->addedLines,
            'removed_lines' => $this->removedLines,
            'title_changed' => $this->titleChanged,
            'content_changed' => $this->contentChanged,
        ];
    }
}
