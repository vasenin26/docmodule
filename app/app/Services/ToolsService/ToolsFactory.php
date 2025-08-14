<?php

namespace App\Services\ToolsService;

use App\Interfaces\GitRepoProviderInterface;
use App\Interfaces\ToolInterface;
use App\Services\ToolsService\Tools\CurrentTime;
use App\Services\ToolsService\Tools\Git\ReadFile;
use App\Services\ToolsService\Tools\SendResult;

class ToolsFactory
{
    public function __construct(
        private GitRepoProviderInterface $gitRepoProvider,
    )
    {

    }

    public function sendResult(): ToolInterface
    {
        return new SendResult();
    }

    public function time(): ToolInterface
    {
        return new CurrentTime();
    }

    public function gitReadFile(): ToolInterface
    {
        return new ReadFile($this->gitRepoProvider);
    }
}
