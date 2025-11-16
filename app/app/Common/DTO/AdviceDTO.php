<?php

namespace App\Common\DTO;

class AdviceDTO
{
    public function __construct(
        public readonly int $projectId,
        public readonly ?string $group,
        public readonly ?string $testField,
        public readonly string $content,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            projectId: (int) ($data['project_id'] ?? 0),
            group: $data['group'] ?? null,
            testField: $data['test_field'] ?? null,
            content: $data['content'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'project_id' => $this->projectId,
            'group' => $this->group,
            'test_field' => $this->testField,
            'content' => $this->content,
        ];
    }
}
