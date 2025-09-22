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
        $promptTokens = 50;
        $completionTokens = 100;
        $totalTokens = 150;

        $chat = LLMChat::create([
            'messages' => $messages,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $totalTokens,
        ]);

        $this->assertEquals($messages, $chat->messages);
        $this->assertEquals($promptTokens, $chat->prompt_tokens);
        $this->assertEquals($completionTokens, $chat->completion_tokens);
        $this->assertEquals($totalTokens, $chat->total_tokens);
        $this->assertTrue($chat->hasMessages());
        $this->assertTrue($chat->isTokensCalculated());
        $this->assertEquals($promptTokens, $chat->getPromptTokensOrZero());
        $this->assertEquals($completionTokens, $chat->getCompletionTokensOrZero());
        $this->assertEquals($totalTokens, $chat->getTotalTokensOrZero());
        $this->assertEquals($totalTokens, $chat->getTokensOrZero()); // Legacy method
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
        $this->assertNull($chat->prompt_tokens);
        $this->assertNull($chat->completion_tokens);
        $this->assertNull($chat->total_tokens);
        $this->assertTrue($chat->hasMessages());
        $this->assertFalse($chat->isTokensCalculated());
        $this->assertEquals(0, $chat->getPromptTokensOrZero());
        $this->assertEquals(0, $chat->getCompletionTokensOrZero());
        $this->assertEquals(0, $chat->getTotalTokensOrZero());
        $this->assertEquals(0, $chat->getTokensOrZero()); // Legacy method
    }

    /** @test */
    public function it_allows_updating_tokens_without_changing_messages()
    {
        $messages = [
            ['role' => 'user', 'content' => 'Test message'],
        ];

        $chat = LLMChat::create([
            'messages' => $messages,
            'total_tokens' => null,
        ]);

        // Должно разрешить обновление токенов
        $chat->update(['total_tokens' => 200]);

        $this->assertEquals(200, $chat->fresh()->total_tokens);
        $this->assertEquals($messages, $chat->fresh()->messages);
    }

    /** @test */
    public function it_allows_creating_chat_with_empty_messages()
    {
        $chat = LLMChat::create([
            'messages' => [],
            'total_tokens' => 50,
        ]);

        $this->assertFalse($chat->hasMessages());
        $this->assertTrue($chat->isTokensCalculated());
        $this->assertEquals(50, $chat->getTotalTokensOrZero());
        $this->assertEquals(50, $chat->getTokensOrZero()); // Legacy method

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
        $chatWithTotalTokens = new LLMChat(['total_tokens' => 100]);
        $this->assertTrue($chatWithTotalTokens->isTokensCalculated());

        $chatWithPromptTokens = new LLMChat(['prompt_tokens' => 50]);
        $this->assertTrue($chatWithPromptTokens->isTokensCalculated());

        $chatWithCompletionTokens = new LLMChat(['completion_tokens' => 75]);
        $this->assertTrue($chatWithCompletionTokens->isTokensCalculated());

        $chatWithZeroTokens = new LLMChat(['total_tokens' => 0]);
        $this->assertTrue($chatWithZeroTokens->isTokensCalculated());

        $chatWithoutTokens = new LLMChat(['total_tokens' => null, 'prompt_tokens' => null, 'completion_tokens' => null]);
        $this->assertFalse($chatWithoutTokens->isTokensCalculated());
    }

    /** @test */
    public function get_tokens_or_zero_returns_correct_values()
    {
        // Test detailed token methods
        $chatWithDetailedTokens = new LLMChat([
            'prompt_tokens' => 50,
            'completion_tokens' => 100,
            'total_tokens' => 150
        ]);
        $this->assertEquals(50, $chatWithDetailedTokens->getPromptTokensOrZero());
        $this->assertEquals(100, $chatWithDetailedTokens->getCompletionTokensOrZero());
        $this->assertEquals(150, $chatWithDetailedTokens->getTotalTokensOrZero());
        $this->assertEquals(150, $chatWithDetailedTokens->getTokensOrZero()); // Legacy method

        // Test with zero tokens
        $chatWithZeroTokens = new LLMChat(['total_tokens' => 0]);
        $this->assertEquals(0, $chatWithZeroTokens->getTotalTokensOrZero());
        $this->assertEquals(0, $chatWithZeroTokens->getTokensOrZero()); // Legacy method

        // Test without tokens
        $chatWithoutTokens = new LLMChat(['total_tokens' => null]);
        $this->assertEquals(0, $chatWithoutTokens->getPromptTokensOrZero());
        $this->assertEquals(0, $chatWithoutTokens->getCompletionTokensOrZero());
        $this->assertEquals(0, $chatWithoutTokens->getTotalTokensOrZero());
        $this->assertEquals(0, $chatWithoutTokens->getTokensOrZero()); // Legacy method
    }
}
