<?php

namespace App\Common\DTO\Page;

use App\Models\Page;
use Illuminate\Database\Eloquent\Collection;

readonly class PageHierarchyDTO
{
    public function __construct(
        public int $id,
        public string $title,
        public array $children
    ) {}

    public static function fromPage(Page $page): self
    {
        return new self(
            id: $page->id,
            title: $page->currentVersion?->title ?? 'Без названия',
            children: []
        );
    }

    public static function fromCollection(Collection $pages): array
    {
        return $pages->map(function (Page $page) {
            return [
                'id' => $page->id,
                'title' => $page->currentVersion?->title ?? 'Без названия',
                'children' => $page->children->map(function (Page $child) {
                    return [
                        'id' => $child->id,
                        'title' => $child->currentVersion?->title ?? 'Без названия',
                        'children' => []
                    ];
                })->toArray()
            ];
        })->toArray();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'children' => $this->children,
        ];
    }
}
