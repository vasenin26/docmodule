<?php

namespace App\Common\DTO;

use App\Models\Page;

readonly class PageApiDTO
{
    public function __construct(
        public int $id,
        public string $title,
        public string $content,
        public array $files
    ) {}

    public static function fromPage(Page $page): self
    {
        $currentVersion = $page->currentVersion;
        
        return new self(
            id: $page->id,
            title: $currentVersion?->title ?? 'Без названия',
            content: $currentVersion?->content ?? '',
            files: $currentVersion?->files ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'files' => $this->files,
        ];
    }
}
