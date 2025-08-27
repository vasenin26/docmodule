<?php

namespace App\Common\DTO;

use App\Common\Enums\PromptType;

class PromptDTO
{
    public function __construct(
        public readonly PromptType $type,
        public readonly string $content,
        public readonly bool $isDefault,
        public readonly int $projectId,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            type: PromptType::from($data['type']),
            content: $data['content'],
            isDefault: $data['is_default'] ?? false,
            projectId: $data['project_id'],
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'content' => $this->content,
            'is_default' => $this->isDefault,
            'project_id' => $this->projectId,
        ];
    }
}
