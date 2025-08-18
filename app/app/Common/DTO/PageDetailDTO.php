<?php

namespace App\Common\DTO;

use App\Models\Page;
use App\Models\PageVersion;
use App\Models\Actualization;

readonly class PageDetailDTO
{
    public function __construct(
        public array $page,
        public ?array $currentDraft,
        public bool $hasActiveDraft,
        public bool $canCreateTask,
        public ?array $actualizationInfo,
        public bool $hasActiveActualization,
        public bool $isActualized
    ) {}

    public static function fromPage(Page $page): self
    {
        $pageData = [
            'id' => $page->id,
            'title' => $page->title ?? 'Без названия',
            'content' => $page->content ?? '',
            'files' => $page->files ?? [],
            'parent_id' => $page->parent_id,
            'project_id' => $page->project_id,
            'created_by' => $page->created_by,
            'created_at' => $page->created_at->toISOString(),
            'updated_at' => $page->updated_at->toISOString(),
            'previous_version_id' => $page->currentVersion?->previous_version_id,
            'version_id' => $page->version_id,
            'creator' => [
                'id' => $page->creator->id,
                'name' => $page->creator->name,
                'email' => $page->creator->email,
            ],
            'project' => $page->project ? [
                'id' => $page->project->id,
                'title' => $page->project->title,
            ] : null,
            'parent' => $page->parent ? [
                'id' => $page->parent->id,
                'title' => $page->parent->title ?? 'Без названия',
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
            'diff_descriptions' => $page->diffDescriptions->map(function ($diffDescription) {
                return [
                    'id' => $diffDescription->id,
                    'content' => $diffDescription->content,
                    'created_at' => $diffDescription->created_at->toISOString(),
                    'creator' => $diffDescription->creator ? [
                        'id' => $diffDescription->creator->id,
                        'name' => $diffDescription->creator->name,
                    ] : null,
                ];
            })->toArray(),
            'latest_actualization' => $page->latestActualization ? [
                'id' => $page->latestActualization->id,
                'status' => $page->latestActualization->status,
                'created_at' => $page->latestActualization->created_at->toISOString(),
                'created_by' => $page->latestActualization->createdBy ? [
                    'id' => $page->latestActualization->createdBy->id,
                    'name' => $page->latestActualization->createdBy->name,
                ] : null,
                'llm_chat' => $page->latestActualization->llmChat ? [
                    'id' => $page->latestActualization->llmChat->id,
                    'messages_count' => $page->latestActualization->llmChat->messages_count ?? 0,
                ] : null,
            ] : null,
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

        $actualizationInfo = null;
        if ($page->actualizationInfo) {
            $actualizationInfo = [
                'id' => $page->actualizationInfo->id,
                'status' => $page->actualizationInfo->status,
                'created_at' => $page->actualizationInfo->created_at->toISOString(),
            ];
        }

        return new self(
            page: $pageData,
            currentDraft: $currentDraft,
            hasActiveDraft: $page->hasActiveDraft ?? false,
            canCreateTask: $page->canCreateTask ?? false,
            actualizationInfo: $actualizationInfo,
            hasActiveActualization: $page->hasActiveActualization ?? false,
            isActualized: $page->isActualized ?? false
        );
    }

    public function toArray(): array
    {
        return array_merge($this->page, [
            'currentDraft' => $this->currentDraft,
            'hasActiveDraft' => $this->hasActiveDraft,
            'canCreateTask' => $this->canCreateTask,
            'actualizationInfo' => $this->actualizationInfo,
            'hasActiveActualization' => $this->hasActiveActualization,
            'isActualized' => $this->isActualized,
        ]);
    }
}
