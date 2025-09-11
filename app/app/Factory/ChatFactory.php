<?php

namespace App\Factory;

use App\Common\DTO\ActualizationContextDTO;
use App\Common\DTO\DifferenceDataDTO;
use App\Common\DTO\GeneratorContextDTO;
use App\Interfaces\Factory\LLMChatFactoryInterface;
use App\Interfaces\LLM\PromptProviderInterface;
use App\Models\LLMChat;
use Vasenin26\Conversation\Chat;
use Vasenin26\Conversation\Messages\SystemMessage;
use Vasenin26\Conversation\Messages\UserMessage;


class ChatFactory implements LLMChatFactoryInterface
{
    private function createChat(array $messages): LLMChat
    {
        return LLMChat::create([
            'messages' => $messages,
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
        ]);
    }

    public function createChatForGenerateDescription(PromptProviderInterface $promptProvider, DifferenceDataDTO $differenceData, array $repositories = [], array $attachedFiles = []): LLMChat
    {
        $prompt = $promptProvider->getDescriptionGeneratorInstructions($differenceData, $repositories, $attachedFiles);
        $role = $promptProvider->getDescriptionGeneratorRole();

        $conversation = new Chat();
        $conversation->addMessage(new SystemMessage($role));
        $conversation->addMessage(new UserMessage($role));

        return $this->createChat($conversation->serialize());
    }

    public function createChatForTechplane(PromptProviderInterface $promptProvider, string $taskDescription, GeneratorContextDTO $context): LLMChat
    {
        $prompt = $promptProvider->getTechplaneGeneratorInstructions($taskDescription, $context);
        $role = $promptProvider->getTechLeadRole();

        $conversation = new Chat();
        $conversation->addMessage(new SystemMessage($role));
        $conversation->addMessage(new UserMessage($role));

        return $this->createChat($conversation->serialize());
    }

    public function createChatForActualization(PromptProviderInterface $promptProvider, string $currentContent, ActualizationContextDTO $context): LLMChat
    {
        $prompt = $promptProvider->getActualizationInstructions($currentContent, $context);
        $role = $promptProvider->getDocumentationSpecialistRole();

        $conversation = new Chat();
        $conversation->addMessage(new SystemMessage($role));
        $conversation->addMessage(new UserMessage($role));

        return $this->createChat($conversation->serialize());
    }
}
