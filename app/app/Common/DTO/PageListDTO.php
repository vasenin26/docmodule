<?php

namespace App\Common\DTO;

use App\Models\Page;
use App\Models\PageVersion;
use App\Models\Actualization;
use Illuminate\Pagination\LengthAwarePaginator;

readonly class PageListDTO
{
    public function __construct(
        public array $pages,
        public array $pagination,
        public array $filters
    ) {}

    public static function fromPaginator(LengthAwarePaginator $paginator, array $filters = []): self
    {
        $pages = $paginator->getCollection()->map(function (Page $page) {
            return [
                'id' => $page->id,
                'title' => $page->title ?? 'Без названия',
                'content' => $page->content ?? '',
                'files' => $page->files ?? [],
                'parent_id' => $page->parent_id,
                'project_id' => $page->project_id,
                'created_by' => $page->created_by,
                'created_at' => $page->created_at->toISOString(),
                'updated_at' => $page->updated_at->toISOString(),
                'creator' => [
                    'id' => $page->creator->id,
                    'name' => $page->creator->name,
                    'email' => $page->creator->email,
                ],
                'project' => $page->project ? [
                    'id' => $page->project->id,
                    'title' => $page->project->title,
                ] : null,
                'children' => $page->children->map(function (Page $child) {
                    return [
                        'id' => $child->id,
                        'title' => $child->title ?? 'Без названия',
                        'creator' => [
                            'id' => $child->creator->id,
                            'name' => $child->creator->name,
                        ],
                    ];
                })->toArray(),
                'has_active_draft' => $page->hasActiveDraft ?? false,
                'is_actualized' => $page->isActualized ?? false,
                'actualization_info' => $page->actualizationInfo ? [
                    'id' => $page->actualizationInfo->id,
                    'status' => $page->actualizationInfo->status,
                    'created_at' => $page->actualizationInfo->created_at->toISOString(),
                ] : null,
            ];
        })->toArray();

        $pagination = [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'links' => $paginator->linkCollection()->toArray(),
        ];

        return new self($pages, $pagination, $filters);
    }

    public function toArray(): array
    {
        return [
            'data' => $this->pages,
            'links' => $this->pagination['links'] ?? [],
        ];
    }
}
