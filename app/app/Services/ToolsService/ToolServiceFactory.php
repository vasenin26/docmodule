<?php

namespace App\Services\ToolsService;

use App\Interfaces\Factory\ToolServiceFactoryInterface;
use App\Interfaces\LLM\LLMTools;

class ToolServiceFactory implements ToolServiceFactoryInterface
{
    public function __construct(
        private ToolsFactory $factory
    )
    {
    }

    public function withAllTools(): LLMTools
    {
        return new ToolsService(
            $this->factory->sendResult(),
            [
                'time' => $this->factory->time()
            ]
        );
    }
}
