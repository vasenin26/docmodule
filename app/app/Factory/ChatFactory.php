<?php

namespace App\Factory;

use App\Common\DTO\DifferenceDataDTO;
use App\Interfaces\Factory\LLMChatFactoryInterface;
use App\Interfaces\LLM\PromptProviderInterface;
use App\Models\LLMChat;


class ChatFactory implements LLMChatFactoryInterface
{
    public function __construct(
        private PromptProviderInterface $promptProvider,
    )
    {
    }

    public function createChatForGenerateDescription(DifferenceDataDTO $differenceData, array $repositories = [], array $attachedFiles = []): LLMChat
    {
        $prompt = $this->promptProvider->getDescriptionGeneratorInstructions($differenceData, $repositories, $attachedFiles);
        $role = $this->promptProvider->getDescriptionGeneratorRole();

        $chat = new LLMChat([
            'messages' => [],
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
        ]);

        $chat->addSystemMessage($role);
        $chat->addUserMessage($prompt);

        $chat->save();

        return $chat;
    }
}
