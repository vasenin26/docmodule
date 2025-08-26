<?php

namespace App\Services\PromptProvider;

interface PromptTemplateRendererInterface
{
    public function render(string $template, array $variables): string;
}
