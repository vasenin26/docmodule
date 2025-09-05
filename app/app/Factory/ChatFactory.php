<?php

namespace App\Factory;

use App\Common\DTO\DifferenceDataDTO;
use App\Common\DTO\GeneratorContextDTO;
use App\Interfaces\Factory\LLMChatFactoryInterface;
use App\Interfaces\LLM\PromptProviderInterface;
use App\Models\LLMChat;


class ChatFactory implements LLMChatFactoryInterface
{
    private function createBasicChat(): LLMChat
    {
        return new LLMChat([
            'messages' => [],
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
        ]);
    }

    public function createChatForGenerateDescription(PromptProviderInterface $promptProvider, DifferenceDataDTO $differenceData, array $repositories = [], array $attachedFiles = []): LLMChat
    {
        $prompt = $promptProvider->getDescriptionGeneratorInstructions($differenceData, $repositories, $attachedFiles);
        $role = $promptProvider->getDescriptionGeneratorRole();

        $chat = $this->createBasicChat();
        $chat->addSystemMessage($role);
        $chat->addUserMessage($prompt);
        $chat->save();

        return $chat;
    }

    public function createChatForTechplane(PromptProviderInterface $promptProvider, string $taskDescription, GeneratorContextDTO $context): LLMChat
    {
        $prompt = $promptProvider->getTechplaneGeneratorInstructions($taskDescription, $context);
        $role = $promptProvider->getTechLeadRole();

        $chat = $this->createBasicChat();
        $chat->addSystemMessage($role);
        $chat->addUserMessage($prompt);
        $chat->save();

        return $chat;
    }
}
