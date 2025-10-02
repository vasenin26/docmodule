<?php

namespace App\Common\DTO\Page;

class PageDataDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $content,
        public readonly array $files = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'] ?? '',
            content: $data['content'] ?? '',
            files: $data['files'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'content' => $this->content,
            'files' => $this->files,
        ];
    }
}
