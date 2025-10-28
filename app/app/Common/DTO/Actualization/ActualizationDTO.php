<?php

namespace App\Common\DTO\Actualization;

class ActualizationDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $pageId,
        public readonly int $pageVersionId,
        public readonly string $status,
        public readonly ?int $llmChatId = null,
    ) {}

    public static function fromModel(\App\Models\Actualization $actualization): self
    {
        return new self(
            id: $actualization->id,
            pageId: $actualization->page_id,
            pageVersionId: $actualization->page_version_id,
            status: $actualization->status,
            llmChatId: $actualization->llm_chat_id
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'page_id' => $this->pageId,
            'page_version_id' => $this->pageVersionId,
            'status' => $this->status,
            'llm_chat_id' => $this->llmChatId
        ];
    }
}
