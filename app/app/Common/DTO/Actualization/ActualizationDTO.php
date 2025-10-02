<?php

namespace App\Common\DTO\Actualization;

class ActualizationDTO
{
    public function __construct(
        public readonly int $pageId,
        public readonly int $pageVersionId,
        public readonly string $status,
        public readonly ?int $llmChatId = null,
        public readonly int $createdBy,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {}

    public static function fromModel(\App\Models\Actualization $actualization): self
    {
        return new self(
            pageId: $actualization->page_id,
            pageVersionId: $actualization->page_version_id,
            status: $actualization->status,
            llmChatId: $actualization->llm_chat_id,
            createdBy: $actualization->created_by,
            createdAt: $actualization->created_at?->toISOString(),
            updatedAt: $actualization->updated_at?->toISOString()
        );
    }

    public function toArray(): array
    {
        return [
            'page_id' => $this->pageId,
            'page_version_id' => $this->pageVersionId,
            'status' => $this->status,
            'llm_chat_id' => $this->llmChatId,
            'created_by' => $this->createdBy,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
