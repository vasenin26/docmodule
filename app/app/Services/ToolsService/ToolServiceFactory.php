<?php

namespace App\Services\ToolsService;

use App\Interfaces\LLM\LLMTools;

class ToolServiceFactory
{
    public function __construct(
        private ToolsFactory $factory
    )
    {
    }

    public function all(): LLMTools
    {
        return new ToolsService(
            $this->factory->sendResult(),
            [
                'time' => $this->factory->time()
            ]
        );
    }
}
