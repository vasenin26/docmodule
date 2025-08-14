<?php

namespace App\Interfaces;

use CzProject\GitPhp\GitRepository;

interface GitRepoProviderInterface
{

    public function getRepo(string $url): GitRepository;
}
