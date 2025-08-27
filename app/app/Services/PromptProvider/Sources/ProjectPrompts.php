<?php

namespace App\Services\PromptProvider\Sources;

use App\Common\Enums\PromptType;
use App\Models\Prompt;
use App\Services\PromptProvider\Interface\PromptSourceInterface;

class ProjectPrompts implements PromptSourceInterface
{
    public function __construct(
        private readonly int $projectId
    ) {}

    public function getPrompt(PromptType $type): ?string
    {
        $prompt = Prompt::where('project_id', $this->projectId)
            ->where('type', $type->value)
            ->first();

        return $prompt?->content;
    }
}
