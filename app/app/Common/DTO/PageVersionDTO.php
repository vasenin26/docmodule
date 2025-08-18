<?php

namespace App\Common\DTO;

use App\Models\Page;

readonly class PageVersionDTO
{
    public function __construct(
        public int $page_id,
        public int $version_id,
        public string $title,
        public string $content,
        public array $files,
        public string $created_at
    )
    {
    }
}
