<?php

namespace Tests\Feature\Factory;

use App\Factory\ChatFactory;
use App\Interfaces\ContentGenerator\DiffGeneratorInterface;
use App\Interfaces\LLM\PromptProviderInterface;
use App\Models\LLMChat;
use App\Models\PageVersion;
use App\Models\ProjectFile;
use App\Models\VersionDiffTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatFactoryGenerateDescriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_chat_contains_diff_prompt_and_attached_git_files(): void
    {
        $version = PageVersion::factory()->create();

        $file = ProjectFile::factory()->create([
            'project_id' => $version->page->project_id,
            'url' => 'https://github.com/example/repo/blob/main/README.md',
        ]);
        $version->projectFiles()->attach($file->id);

        $task = VersionDiffTask::factory()->create([
            'project_id' => $version->page->project_id,
            'page_version_id' => null,
            'generation_status' => VersionDiffTask::STATUS_PENDING,
            'llm_chat_id' => null,
        ]);
        $task->pageVersions()->sync([$version->id]);

        $diff = new \App\Common\DTO\DifferenceDataDTO(
            diffOutput: "--- old\n+++ new\n+Test diff",
            newVersionTitle: $version->title,
            isNewPage: false,
            titleChanged: true,
            contentChanged: true,
            newVersionId: $version->id,
            newVersionContent: $version->content,
            previousVersionId: null,
            previousVersionTitle: null,
            previousVersionContent: null,
        );

        $diffGenerator = $this->createMock(DiffGeneratorInterface::class);
        $diffGenerator->method('createDifferenceDataDTO')->willReturn($diff);

        $promptProvider = $this->createMock(PromptProviderInterface::class);
        $promptProvider->method('getDescriptionGeneratorRole')->willReturn('You are a helpful assistant.');
        $promptProvider->method('getDescriptionGeneratorInstructions')->willReturn('[DIFF]\nTest diff');

        $factory = new ChatFactory($diffGenerator);
        $chat = $factory->createChatForGenerateDescription($promptProvider, $task);

        $this->assertInstanceOf(LLMChat::class, $chat);
        $this->assertIsArray($chat->messages);

        $hasPrompt = false;
        $hasGitFile = false;
        foreach ($chat->messages as $message) {
            if (($message['type'] ?? null) === 'user' && str_contains($message['message']['content'] ?? '', 'Test diff')) {
                $hasPrompt = true;
            }
            if (($message['type'] ?? null) === 'git-file' && ($message['message']['url'] ?? null) === $file->url) {
                $hasGitFile = true;
            }
        }

        $this->assertTrue($hasPrompt, 'User prompt must contain diff content');
        $this->assertTrue($hasGitFile, 'Git file message with attached file URL must be present');
    }
}


