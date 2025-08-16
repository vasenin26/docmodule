<?php

namespace App\Services\RepositoryService;

use App\Interfaces\GitRepoProviderInterface;
use CzProject\GitPhp\Git;
use CzProject\GitPhp\GitRepository;
use Illuminate\Support\Facades\Log;

class RepositoryProvider implements GitRepoProviderInterface
{

    public function getRepo(string $url): GitRepository
    {
        $domain = '';
        $path = '';

        // Парсинг URL в зависимости от формата
        if (str_starts_with($url, 'https://')) {
            // HTTPS формат
            $parsed_url = parse_url($url);
            $domain = $parsed_url['host'];
            $path = trim($parsed_url['path'], '/');
        } elseif (str_starts_with($url, 'git@')) {
            // SSH формат: git@github.com:username/repository.git
            $parts = explode(':', $url);
            if (count($parts) === 2) {
                $domainPart = $parts[0]; // git@github.com
                $pathPart = $parts[1];   // username/repository.git
                
                // Извлекаем домен из git@domain
                $domainParts = explode('@', $domainPart);
                if (count($domainParts) === 2) {
                    $domain = $domainParts[1]; // github.com
                }
                
                $path = $pathPart;
            }
        } else {
            throw new \InvalidArgumentException('Неподдерживаемый формат URL репозитория');
        }

        // Убираем .git из пути если есть
        $path = preg_replace('/\.git$/', '', $path);

        $fullPath = '/var/repos/' . $domain . '/' . $path;

        Log::info('Repository path: ' . $fullPath);

        $git = new Git();

        if (is_dir($fullPath)) {
            $repo = $git->open($fullPath);
        } else {
            $repo = $git->cloneRepository($url, $fullPath);
        }

        return $repo;
    }
}
