<?php

namespace App\Services\PromptProvider\Sources;

use App\Common\Enums\PromptType;
use App\Services\PromptProvider\Interface\PromptSourceInterface;

class SafePromptSource implements PromptSourceInterface
{
    public function __construct(
        private readonly PromptSourceInterface $primarySource,
        private readonly PromptSourceInterface $fallbackSource,
    ) {}

    public function getPrompt(PromptType $type): ?string
    {
        $prompt = $this->primarySource->getPrompt($type);
        
        if ($prompt !== null && trim($prompt) !== '') {
            return $prompt;
        }
        
        return $this->fallbackSource->getPrompt($type);
    }
}
