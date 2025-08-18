<?php

namespace App\Common\DTO;

use App\Models\Page;
use App\Models\PageVersion;
use App\Models\Actualization;

class PageAggregateDTO
{
    public function __construct(
        public readonly Page $page,
        public readonly ?PageVersion $currentDraft,
        public readonly bool $hasActiveDraft,
        public readonly bool $canCreateTask,
        public readonly ?Actualization $actualizationInfo,
        public readonly bool $hasActiveActualization,
        public readonly bool $isActualized
    ) {}

    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'current_draft' => $this->currentDraft,
            'has_active_draft' => $this->hasActiveDraft,
            'can_create_task' => $this->canCreateTask,
            'actualization_info' => $this->actualizationInfo,
            'has_active_actualization' => $this->hasActiveActualization,
            'is_actualized' => $this->isActualized,
        ];
    }
}
