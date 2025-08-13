<?php

namespace App\Services\LLMGenerator\Tools;

interface ToolInterface
{
    public function execute($args): ?string;
    public function getProps($name): array;
}
