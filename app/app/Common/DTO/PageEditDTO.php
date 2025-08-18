<?php

namespace App\Common\DTO;

use App\Models\Page;
use App\Models\PageVersion;

readonly class PageEditDTO
{
    public function __construct(
        public array $page,
        public ?array $currentDraft,
        public bool $hasActiveDraft,
        public bool $canCreateTask
    ) {}

    public static function fromPage(Page $page): self
    {
        $pageData = [
            'id' => $page->id,
            'title' => $page->currentVersion?->title ?? 'Без названия',
            'content' => $page->currentVersion?->content ?? '',
            'files' => $page->currentVersion?->files ?? [],
            'parent_id' => $page->parent_id,
            'project_id' => $page->project_id,
            'created_by' => $page->created_by,
            'created_at' => $page->created_at->toISOString(),
            'updated_at' => $page->updated_at->toISOString(),
            'creator' => $page->creator ? [
                'id' => $page->creator->id,
                'name' => $page->creator->name,
                'email' => $page->creator->email,
            ] : null,
            'project' => $page->project ? [
                'id' => $page->project->id,
                'title' => $page->project->title,
            ] : null,
            'parent' => $page->parent ? [
                'id' => $page->parent->id,
                'title' => $page->parent->currentVersion?->title ?? 'Без названия',
            ] : null,
            'children' => $page->children->map(fn($child) => [
                'id' => $child->id,
                'title' => $child->currentVersion?->title ?? 'Без названия',
            ])->toArray(),
        ];

        $currentDraft = null;
        if ($page->currentDraft) {
            $currentDraft = [
                'id' => $page->currentDraft->id,
                'title' => $page->currentDraft->title,
                'content' => $page->currentDraft->content,
                'files' => $page->currentDraft->files ?? [],
                'created_at' => $page->currentDraft->created_at->toISOString(),
                'updated_at' => $page->currentDraft->updated_at->toISOString(),
            ];
        }

        return new self(
            page: $pageData,
            currentDraft: $currentDraft,
            hasActiveDraft: $page->hasActiveDraft ?? false,
            canCreateTask: $page->canCreateTask ?? false
        );
    }

    public function toArray(): array
    {
        return array_merge($this->page, [
            'currentDraft' => $this->currentDraft,
            'hasActiveDraft' => $this->hasActiveDraft,
            'canCreateTask' => $this->canCreateTask,
        ]);
    }
}
