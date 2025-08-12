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
        public bool    $contentChanged = false,
        public ?int    $newVersionId = null,
        public ?string $newVersionContent = null,
        public ?int    $previousVersionId = null,
        public ?string $previousVersionTitle = null,
        public ?string $previousVersionContent = null
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
            contentChanged: $data['content_changed'] ?? false,
            newVersionId: $data['new_version_id'] ?? null,
            newVersionContent: $data['new_version_content'] ?? null,
            previousVersionId: $data['previous_version_id'] ?? null,
            previousVersionTitle: $data['previous_version_title'] ?? null,
            previousVersionContent: $data['previous_version_content'] ?? null
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
            'new_version_id' => $this->newVersionId,
            'new_version_content' => $this->newVersionContent,
            'previous_version_id' => $this->previousVersionId,
            'previous_version_title' => $this->previousVersionTitle,
            'previous_version_content' => $this->previousVersionContent,
        ];
    }
}
