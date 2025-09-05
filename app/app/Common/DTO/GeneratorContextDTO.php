<?php

namespace App\Common\DTO;

readonly class GeneratorContextDTO
{
    public function __construct(
        public array $attachedFiles = [],
        public array $repositories = [],
        public ?int $projectId = null,
        public array $additionalContext = [], // Для будущих расширений
    ) {}

    public function toArray(): array
    {
        return [
            'attached_files' => $this->attachedFiles,
            'repositories' => $this->repositories,
            'project_id' => $this->projectId,
            'additional_context' => $this->additionalContext,
        ];
    }
}
