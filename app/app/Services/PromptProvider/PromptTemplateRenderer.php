<?php

namespace App\Services\PromptProvider;

use App\Services\PromptProvider\Interface\PromptTemplateRendererInterface;

class PromptTemplateRenderer implements PromptTemplateRendererInterface
{
    private \Mustache_Engine $mustache;

    public function __construct()
    {
        $this->mustache = new \Mustache_Engine();
    }

    public function render(string $template, array $variables): string
    {
        return $this->mustache->render($template, $variables);
    }
}
