<?php

namespace App\Interfaces;

interface ToolInterface
{
    public function execute($args): ?string;
    public function getProps($name): array;
}
