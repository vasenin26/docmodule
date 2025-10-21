<?php

namespace Tests\Unit\Models;

use App\Models\LLMChat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LLMChatTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_llm_chat_with_detailed_tokens()
    {
        $messages = [
            ['role' => 'user', 'content' => 'Test message'],
            ['role' => 'assistant', 'content' => 'Test response'],
        ];

        $chat = LLMChat::create([
            'messages' => $messages,
        ]);

        $this->assertEquals($messages, $chat->messages);
        $this->assertTrue($chat->hasMessages());
        $this->assertFalse($chat->isTokensCalculated()); // Нет связанных agent_tasks
        $this->assertEquals(0, $chat->getPromptTokensOrZero());
        $this->assertEquals(0, $chat->getCompletionTokensOrZero());
        $this->assertEquals(0, $chat->getTotalTokensOrZero());
        $this->assertEquals(0, $chat->getTokensOrZero()); // Legacy method
    }

    /** @test */
    public function it_can_create_llm_chat_without_tokens()
    {
        $messages = [
            ['role' => 'user', 'content' => 'Test message'],
        ];

        $chat = LLMChat::create([
            'messages' => $messages,
        ]);

        $this->assertEquals($messages, $chat->messages);
        $this->assertTrue($chat->hasMessages());
        $this->assertFalse($chat->isTokensCalculated());
        $this->assertEquals(0, $chat->getPromptTokensOrZero());
        $this->assertEquals(0, $chat->getCompletionTokensOrZero());
        $this->assertEquals(0, $chat->getTotalTokensOrZero());
        $this->assertEquals(0, $chat->getTokensOrZero()); // Legacy method
    }

    /** @test */
    public function it_allows_updating_messages()
    {
        $messages = [
            ['role' => 'user', 'content' => 'Test message'],
        ];

        $chat = LLMChat::create([
            'messages' => $messages,
        ]);

        // Должно разрешить обновление сообщений
        $newMessages = [
            ['role' => 'user', 'content' => 'Updated message'],
        ];
        $chat->update(['messages' => $newMessages]);

        $this->assertEquals($newMessages, $chat->fresh()->messages);
    }

    /** @test */
    public function it_allows_creating_chat_with_empty_messages()
    {
        $chat = LLMChat::create([
            'messages' => [],
        ]);

        $this->assertFalse($chat->hasMessages());
        $this->assertFalse($chat->isTokensCalculated());
        $this->assertEquals(0, $chat->getTotalTokensOrZero());
        $this->assertEquals(0, $chat->getTokensOrZero()); // Legacy method

        // Должно разрешить обновление пустых сообщений
        $newMessages = [
            ['role' => 'user', 'content' => 'New message'],
        ];

        $chat->update(['messages' => $newMessages]);
        $this->assertEquals($newMessages, $chat->fresh()->messages);
    }

    /** @test */
    public function has_messages_returns_false_for_empty_array()
    {
        $chat = new LLMChat(['messages' => []]);
        $this->assertFalse($chat->hasMessages());
    }

    /** @test */
    public function has_messages_returns_false_for_null()
    {
        $chat = new LLMChat(['messages' => null]);
        $this->assertFalse($chat->hasMessages());
    }

    /** @test */
    public function is_tokens_calculated_works_correctly()
    {
        // Без связанных agent_tasks токены не рассчитаны
        $chatWithoutTasks = new LLMChat(['messages' => []]);
        $this->assertFalse($chatWithoutTasks->isTokensCalculated());
    }

    /** @test */
    public function get_tokens_or_zero_returns_correct_values()
    {
        // Test without agent_tasks - все токены должны быть 0
        $chatWithoutTasks = new LLMChat(['messages' => []]);
        $this->assertEquals(0, $chatWithoutTasks->getPromptTokensOrZero());
        $this->assertEquals(0, $chatWithoutTasks->getCompletionTokensOrZero());
        $this->assertEquals(0, $chatWithoutTasks->getTotalTokensOrZero());
        $this->assertEquals(0, $chatWithoutTasks->getTokensOrZero()); // Legacy method
    }
}
