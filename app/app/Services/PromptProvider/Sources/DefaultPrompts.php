<?php

namespace App\Services\PromptProvider\Sources;

use App\Common\Enums\PromptType;
use App\Services\PromptProvider\Interface\PromptSourceInterface;
use Illuminate\Support\Facades\File;

class DefaultPrompts implements PromptSourceInterface
{
    private const PROMPTS_PATH = 'resources/prompts';

    public function getPrompt(PromptType $type): ?string
    {
        $filePath = base_path(self::PROMPTS_PATH . '/' . $type->getFileName());

        if (!File::exists($filePath)) {
            return null;
        }

        return File::get($filePath);
    }
}
