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
        try {
            return $this->mustache->render($template, $variables);
        } catch (\Throwable $e) {
            $map = [];

            foreach ($variables as $key => $value) {
                $map[] = "[$key] => " . print_r($value, true);
            }

            return join("\n",  [
                'Broken template: ' . $e->getMessage(),
                "Available variables: \n\n" . join("\n---\n", $map),
            ]);
        }
    }
}
