<?php

namespace App\Services\PromptProvider\Interface;

use App\Common\Enums\PromptType;

interface PromptSourceInterface
{
    public function getPrompt(PromptType $type): ?string;
}
