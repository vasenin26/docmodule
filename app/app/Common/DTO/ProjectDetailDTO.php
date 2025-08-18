<?php

namespace App\Common\DTO;

use App\Models\Project;
use App\Models\Page;

readonly class ProjectDetailDTO
{
    public function __construct(
        public array $project,
        public array $pages,
        public array $repositories
    ) {}

    public static function fromProject(Project $project): self
    {
        $projectData = [
            'id' => $project->id,
            'title' => $project->title,
            'owner_id' => $project->owner_id,
            'created_at' => $project->created_at->toISOString(),
            'updated_at' => $project->updated_at->toISOString(),
            'owner' => [
                'id' => $project->owner->id,
                'name' => $project->owner->name,
                'email' => $project->owner->email,
            ],
        ];

        $pages = $project->pages->map(function (Page $page) {
            return [
                'id' => $page->id,
                'title' => $page->title ?? 'Без названия',
                'content' => $page->content ?? '',
                'files' => $page->files ?? [],
                'parent_id' => $page->parent_id,
                'created_by' => $page->created_by,
                'created_at' => $page->created_at->toISOString(),
                'updated_at' => $page->updated_at->toISOString(),
                'creator' => [
                    'id' => $page->creator->id,
                    'name' => $page->creator->name,
                ],
                'children_count' => $page->children->count(),
                'has_children' => $page->children->isNotEmpty(),
            ];
        })->toArray();

        $repositories = $project->repositories->map(function ($repository) {
            return [
                'id' => $repository->id,
                'url' => $repository->url,
                'options' => $repository->options,
                'created_at' => $repository->created_at->toISOString(),
                'updated_at' => $repository->updated_at->toISOString(),
            ];
        })->toArray();

        return new self(
            project: $projectData,
            pages: $pages,
            repositories: $repositories
        );
    }

    public function toArray(): array
    {
        return array_merge($this->project, [
            'pages' => $this->pages,
            'repositories' => $this->repositories,
        ]);
    }
}
