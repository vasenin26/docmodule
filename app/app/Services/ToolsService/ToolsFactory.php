<?php

namespace App\Services\ToolsService;

use App\Interfaces\ToolInterface;
use App\Services\ToolsService\Tools\CurrentTime;
use App\Services\ToolsService\Tools\SendResult;

class ToolsFactory
{
    public function sendResult(): ToolInterface
    {
        return new SendResult();
    }

    public function time(): ToolInterface
    {
        return new CurrentTime();
    }
}
