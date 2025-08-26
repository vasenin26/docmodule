<?php

namespace App\Services\PromptProvider\Sources;

use App\Enums\PromptType;
use App\Models\Prompt;

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
