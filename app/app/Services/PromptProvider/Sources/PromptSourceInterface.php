<?php

namespace App\Services\PromptProvider\Sources;

use App\Enums\PromptType;

interface PromptSourceInterface
{
    public function getPrompt(PromptType $type): ?string;
}
