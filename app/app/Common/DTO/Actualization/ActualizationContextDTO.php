<?php

namespace App\Common\DTO\Actualization;

readonly class ActualizationContextDTO
{
    public function __construct(
        public array $attachedFiles = [],
        public array $repositories = [],
        public ?int $projectId = null,
        public array $additionalContext = [],
    ) {}

    public function toArray(): array
    {
        return [
            'attached_files' => $this->getFileList(),
            'repositories' => $this->repositories,
            'project_id' => $this->projectId,
            'additional_context' => $this->additionalContext,
        ];
    }

    private function getFileList()
    {
        return array_map(fn($f) => [
            'url' => $f['url'],
            'description' => $f['description'],
        ], $this->attachedFiles);
    }
}
