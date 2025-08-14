<?php

namespace App\Interfaces;

interface ToolInterface
{
    public function execute(array $args): ?string;
    public function getProps($name): array;
}
