<?php

namespace App\Services\PromptProvider\Interface;

interface PromptTemplateRendererInterface
{
    public function render(string $template, array $variables): string;
}
