<?php

namespace App\Interfaces\LLM;

interface PromptProviderInterface
{

    public function getDescriptionGeneratorRole(): string;
}
