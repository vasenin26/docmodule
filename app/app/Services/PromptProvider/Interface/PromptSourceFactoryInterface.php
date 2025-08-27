<?php

namespace App\Services\PromptProvider\Interface;

interface PromptSourceFactoryInterface
{
    public function createDefaultSource(): PromptSourceInterface;

    public function createProjectSource(int $projectId): PromptSourceInterface;
}
