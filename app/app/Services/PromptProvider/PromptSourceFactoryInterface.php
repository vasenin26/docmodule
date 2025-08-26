<?php

namespace App\Services\PromptProvider;

use App\Services\PromptProvider\Sources\PromptSourceInterface;

interface PromptSourceFactoryInterface
{
    public function createDefaultSource(): PromptSourceInterface;
    
    public function createProjectSource(int $projectId): PromptSourceInterface;
}
