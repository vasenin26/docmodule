<?php

namespace App\Common\DTO;

readonly class DifferenceDataDTO
{
    public function __construct(
        public ?string $diffOutput = null,
        public ?string $newVersionTitle = null,
        public bool    $isNewPage = false,
        public array   $addedLines = [],
        public array   $removedLines = [],
        public bool    $titleChanged = false,
        public bool    $contentChanged = false
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
