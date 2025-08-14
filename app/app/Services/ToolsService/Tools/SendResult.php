<?php

namespace App\Services\ToolsService\Tools;

use App\Interfaces\ToolInterface;

class SendResult implements ToolInterface
{

    public function execute($args): ?string
    {
        $content = json_decode($args);

        if(json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $content->content ?? null;
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
