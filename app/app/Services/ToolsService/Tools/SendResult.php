<?php

namespace App\Services\ToolsService\Tools;

use App\Interfaces\ToolInterface;

class SendResult implements ToolInterface
{

    public function execute(array $args): ?string
    {
        return $args['content'] ?? null;
    }

    public function getProps($name): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => 'Сохраняет данные в хранилище.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'content' => [
                            'type' => 'string',
                            'description' => 'Данные для сохранения.',
                        ]
                    ],
                    'required' => ['content'],
                ]
            ]
        ];
    }
}
