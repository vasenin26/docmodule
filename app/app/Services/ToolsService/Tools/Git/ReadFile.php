<?php

namespace App\Services\ToolsService\Tools\Git;

use App\Interfaces\GitRepoProviderInterface;
use App\Interfaces\ToolInterface;

class ReadFile implements ToolInterface
{

    public function __construct(
        private GitRepoProviderInterface $repoProvider
    )
    {
    }

    //read file from git repository
    public function execute($args): ?string
    {
        $content = json_decode($args, true);

        if(json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        list('url' => $url, 'path' => $path) = $content;

        $repo = $this->repoProvider->getRepo($url);
        $fullPath = $repo->getRepositoryPath() . '/' . trim($path, '/');

        $content = file_get_contents($fullPath);

        return $content;
    }

    public function getProps($name): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => 'Read file from repository',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'url' => [
                            'type' => 'string',
                            'description' => 'Git repository url',
                        ],
                        'path' => [
                            'type' => 'string',
                            'description' => 'Path to file',
                        ]
                    ],
                    'required' => ['content'],
                ]
            ]
        ];
    }
}
