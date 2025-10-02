<?php

namespace App\Factory;

use App\Common\DTO\Actualization\ActualizationContextDTO;
use App\Common\DTO\GeneratorContextDTO;
use App\Common\Utils\ExtractRepoUrl;
use App\Interfaces\ContentGenerator\DiffGeneratorInterface;
use App\Interfaces\Factory\LLMChatFactoryInterface;
use App\Interfaces\LLM\PromptProviderInterface;
use App\Models\LLMChat;
use App\Models\VersionDiffTask;
use Vasenin26\Conversation\Chat;
use Vasenin26\Conversation\Messages\GitFileMessage;
use Vasenin26\Conversation\Messages\PageVersionMessage;
use Vasenin26\Conversation\Messages\SystemMessage;
use Vasenin26\Conversation\Messages\UserTaskMessage;


class ChatFactory implements LLMChatFactoryInterface
{
    public function __construct(
        private DiffGeneratorInterface $diffGenerator,
    )
    {
    }

    private function createChat(array $messages): LLMChat
    {
        return LLMChat::create([
            'messages' => $messages,
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
        ]);
    }

    public function createChatForGenerateDescription(PromptProviderInterface $promptProvider, VersionDiffTask $task): LLMChat
    {
        $conversation = new Chat();

        $role = $promptProvider->getDescriptionGeneratorRole();
        $conversation->addMessage(new SystemMessage($role));

        $pageVersions = $task->pageVersions;

        $attached = [];
        $pageDiffs = [];
        foreach ($pageVersions as $pageVersion) {
            $pageDiffs[] = $this->diffGenerator->createDifferenceDataDTO($pageVersion);

            foreach ($pageVersion->projectFiles as $file) {
                if (in_array($file, $attached)) {
                    continue;
                }
                $attached[] = $file;
            }
        }

        $prompt = $promptProvider->getDescriptionGeneratorInstructions(
            differenceData: $pageDiffs,
            repositories: $task->project->repositories->pluck('url')->toArray(),
        );

        $conversation->addMessage(new UserTaskMessage($prompt));

        foreach ($attached as $file) {
            $conversation->addMessage(
                new GitFileMessage(
                    ExtractRepoUrl::extractRepoUrl($file->url),
                    ExtractRepoUrl::extractFilePath($file->url),
                    $file->description
                ));
        }

        return $this->createChat($conversation->serialize());
    }

    public function createChatForTechplane(PromptProviderInterface $promptProvider, string $taskDescription, GeneratorContextDTO $context): LLMChat
    {
        $prompt = $promptProvider->getTechplaneGeneratorInstructions($taskDescription, $context);
        $role = $promptProvider->getTechLeadRole();

        $conversation = new Chat();
        $conversation->addMessage(new SystemMessage($role));
        $conversation->addMessage(new UserTaskMessage($prompt));

        return $this->createChat($conversation->serialize());
    }

    public function createChatForActualization(PromptProviderInterface $promptProvider, string $currentContent, ActualizationContextDTO $context): LLMChat
    {
        $prompt = $promptProvider->getActualizationInstructions($currentContent, $context);
        $role = $promptProvider->getDocumentationSpecialistRole();

        $conversation = new Chat();
        $conversation->addMessage(new SystemMessage($role));
        $conversation->addMessage(new UserTaskMessage($prompt));

        return $this->createChat($conversation->serialize());
    }

    public function createChatForImplementation(PromptProviderInterface $promptProvider, string $techplaneContent, GeneratorContextDTO $context): LLMChat
    {
        $prompt = $promptProvider->getImplementationInstructions($techplaneContent, $context);
        $role = $promptProvider->getDeveloperRole();

        $conversation = new Chat();
        $conversation->addMessage(new SystemMessage($role));
        $conversation->addMessage(new UserTaskMessage($prompt));

        return $this->createChat($conversation->serialize());
    }

    public function createChatForUpdatedTask(PromptProviderInterface $promptProvider, VersionDiffTask $task): LLMChat
    {
        $conversation = new Chat();

        $role = $promptProvider->getDescriptionGeneratorRole();
        $conversation->addMessage(new SystemMessage($role));

        $pageVersions = $task->pageVersions;

        $attached = [];
        foreach ($pageVersions as $pageVersion) {
            $conversation->addMessage(new PageVersionMessage($pageVersion->id));

            foreach ($pageVersion->files as $file) {
                if (in_array($file, $attached)) {
                    continue;
                }
                $attached[] = $file;
            }
        }

        foreach ($attached as $file) {
            $conversation->addMessage(new GitFileMessage($file));
        }

        return $this->createChat($conversation->serialize());
    }
}
