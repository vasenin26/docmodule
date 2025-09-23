<?php

namespace Tests\Unit\Factory;

use App\Factory\ChatFactory;
use App\Interfaces\ContentGenerator\DiffGeneratorInterface;
use App\Interfaces\LLM\PromptProviderInterface;
use App\Models\VersionDiffTask;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChatFactoryTest extends TestCase
{
    #[Test]
    public function it_constructs_with_required_dependencies(): void
    {
        $diffGenerator = $this->createMock(DiffGeneratorInterface::class);

        $factory = new ChatFactory($diffGenerator);

        $this->assertInstanceOf(ChatFactory::class, $factory);
    }

    #[Test]
    public function create_chat_for_description(): void
    {
        $diffGenerator = $this->createMock(DiffGeneratorInterface::class);
        $provider = $this->createMock(PromptProviderInterface::class);

        $provider->method('getDescriptionGeneratorRole')->willReturn('You are a helpful assistant.');
        $provider->method('getDescriptionGeneratorInstructions')->willReturn("[DIFF]\nTest diff");

        $task = VersionDiffTask::factory()->create();
        $pageVersion = \App\Models\PageVersion::factory()->create();

        $file1 = \App\Models\ProjectFile::factory()->create([
            'project_id' => $pageVersion->page->project_id,
        ]);
        $file2 = \App\Models\ProjectFile::factory()->create([
            'project_id' => $pageVersion->page->project_id,
        ]);
        $pageVersion->projectFiles()->attach([$file1->id, $file2->id]);

        $task = VersionDiffTask::factory()->create([
            'project_id' => $pageVersion->page->project_id,
            'page_version_id' => null,
            'generation_status' => VersionDiffTask::STATUS_PENDING,
            'llm_chat_id' => null,
        ]);
        $task->pageVersions()->sync([$pageVersion->id]);

        $factory = new ChatFactory($diffGenerator);
        $chat = $factory->createChatForGenerateDescription($provider, $task);

        $this->assertIsArray($chat->messages);
        $this->assertCount(4, $chat->messages);

        $this->assertEquals('system', $chat->messages[0]['type']);
        $this->assertEquals('You are a helpful assistant.', $chat->messages[0]['message']['content']);

        $this->assertEquals('user', $chat->messages[1]['type']);
        $this->assertEquals("[DIFF]\nTest diff", $chat->messages[1]['message']['content']);

        // Check that at least one message is a git-file message and matches one of the attached files
        $gitFileMessages = array_filter($chat->messages, function ($msg) use ($file1, $file2) {
            return $msg['type'] === 'git-file'
                && (
                    ($msg['message']['url'] ?? null) === $file1->url
                    || ($msg['message']['url'] ?? null) === $file2->url
                );
        });

        $this->assertNotEmpty($gitFileMessages, 'Chat should contain at least one git-file message for attached files.');
    }
}


