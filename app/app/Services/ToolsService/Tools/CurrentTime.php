<?php

namespace App\Services\ToolsService\Tools;

use App\Interfaces\ToolInterface;

class CurrentTime implements ToolInterface
{

    public function execute($args): string
    {
        return (new \DateTime())->format('Y-m-d H:i:s');
    }

    public function getProps($name): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => 'Return current date and time',
            ]
        ];
    }
}
