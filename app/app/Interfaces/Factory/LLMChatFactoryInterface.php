<?php

namespace App\Interfaces\Factory;

use App\Common\DTO\DifferenceDataDTO;
use App\Common\DTO\GeneratorContextDTO;
use App\Interfaces\LLM\PromptProviderInterface;
use App\Models\LLMChat;

interface LLMChatFactoryInterface
{
    public function createChatForGenerateDescription(PromptProviderInterface $promptProvider, DifferenceDataDTO $differenceData, array $repositories = [], array $attachedFiles = []): LLMChat;
    
    public function createChatForTechplane(PromptProviderInterface $promptProvider, string $taskDescription, GeneratorContextDTO $context): LLMChat;
}
