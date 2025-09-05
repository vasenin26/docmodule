<?php

namespace App\Common\DTO;

use App\Models\Page;
use Illuminate\Database\Eloquent\Collection;

readonly class PageListDTO
{
    public function __construct(
        public array $pages
    ) {}

    public static function fromCollection(Collection $pages): self
    {
        $pageList = $pages->map(function (Page $page) {
            return [
                'id' => $page->id,
                'title' => $page->currentVersion?->title ?? 'Без названия'
            ];
        })->toArray();
        
        return new self($pageList);
    }

    public function toArray(): array
    {
        return $this->pages;
    }
}