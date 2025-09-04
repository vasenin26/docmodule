<?php

namespace App\Services\PromptProvider;

use App\Services\PromptProvider\Interface\PromptTemplateRendererInterface;
use Mustache\Engine;

class PromptTemplateRenderer implements PromptTemplateRendererInterface
{
    private Engine $mustache;

    public function __construct()
    {
        $this->mustache = new Engine();
    }

    public function render(string $template, array $variables): string
    {
        return $this->mustache->render($template, $variables);
    }
}
