<?php

namespace App\Interfaces\Factory;

use App\Common\DTO\DifferenceDataDTO;
use App\Models\LLMChat;

interface LLMChatFactoryInterface
{
    public function createChatForGenerateDescription(DifferenceDataDTO $differenceData, array $repositories = [], array $attachedFiles = []): LLMChat;
}
