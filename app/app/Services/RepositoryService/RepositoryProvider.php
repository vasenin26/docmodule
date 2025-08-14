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
        $parsed_url = parse_url($url);
        $domain = $parsed_url['host'];
        $path = trim($parsed_url['path'], '/');
        $path = preg_replace('/\.git$/', '', $path);

        $fullPath = '/var/repos/' . $domain . '/' . $path;

        Log::info($fullPath);

        $git = new Git();

        if (is_dir($fullPath)) {
            $repo = $git->open($fullPath);
        } else {
            $repo = $git->cloneRepository($url, $fullPath);
        }

        return $repo;
    }
}
