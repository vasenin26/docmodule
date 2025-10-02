<?php

namespace App\Common\DTO\Page;

use App\Models\PageVersion;

readonly class DraftDTO
{
    public function __construct(
        public int $id,
        public string $title,
        public string $content,
        public array $files,
        public string $createdAt,
        public string $updatedAt,
        public int $pageId
    ) {}

    public static function fromPageVersion(PageVersion $draft): self
    {
        return new self(
            id: $draft->id,
            title: $draft->title,
            content: $draft->content,
            files: $draft->files ?? [],
            createdAt: $draft->created_at->toISOString(),
            updatedAt: $draft->updated_at->toISOString(),
            pageId: $draft->page_id
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'files' => $this->files,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'page_id' => $this->pageId,
        ];
    }
}
