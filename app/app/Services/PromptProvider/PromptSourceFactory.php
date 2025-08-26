<?php

namespace App\Services\PromptProvider;

use App\Services\PromptProvider\Sources\DefaultPrompts;
use App\Services\PromptProvider\Sources\ProjectPrompts;
use App\Services\PromptProvider\Sources\PromptSourceInterface;

class PromptSourceFactory implements PromptSourceFactoryInterface
{
    public function createDefaultSource(): PromptSourceInterface
    {
        return new DefaultPrompts();
    }
    
    public function createProjectSource(int $projectId): PromptSourceInterface
    {
        return new ProjectPrompts($projectId);
    }
}
