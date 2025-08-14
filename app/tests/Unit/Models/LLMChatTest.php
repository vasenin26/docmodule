<?php

namespace Tests\Unit\Models;

use App\Models\LLMChat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LLMChatTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_llm_chat_with_tokens()
    {
        $messages = [
            ['role' => 'user', 'content' => 'Test message'],
            ['role' => 'assistant', 'content' => 'Test response'],
        ];
        $tokens = 150;

        $chat = LLMChat::create([
            'messages' => $messages,
            'tokens' => $tokens,
        ]);

        $this->assertEquals($messages, $chat->messages);
        $this->assertEquals($tokens, $chat->tokens);
        $this->assertTrue($chat->hasMessages());
        $this->assertTrue($chat->isTokensCalculated());
        $this->assertEquals($tokens, $chat->getTokensOrZero());
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
        $this->assertNull($chat->tokens);
        $this->assertTrue($chat->hasMessages());
        $this->assertFalse($chat->isTokensCalculated());
        $this->assertEquals(0, $chat->getTokensOrZero());
    }

    /** @test */
    public function it_prevents_updating_messages_when_they_already_exist()
    {
        $originalMessages = [
            ['role' => 'user', 'content' => 'Original message'],
        ];

        $chat = LLMChat::create([
            'messages' => $originalMessages,
            'tokens' => 100,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Изменение сохраненной истории переписки запрещено для сохранения исторических данных');

        $chat->update([
            'messages' => [
                ['role' => 'user', 'content' => 'Modified message'],
            ],
        ]);
    }

    /** @test */
    public function it_allows_updating_tokens_without_changing_messages()
    {
        $messages = [
            ['role' => 'user', 'content' => 'Test message'],
        ];

        $chat = LLMChat::create([
            'messages' => $messages,
            'tokens' => null,
        ]);

        // Должно разрешить обновление токенов
        $chat->update(['tokens' => 200]);

        $this->assertEquals(200, $chat->fresh()->tokens);
        $this->assertEquals($messages, $chat->fresh()->messages);
    }

    /** @test */
    public function it_allows_creating_chat_with_empty_messages()
    {
        $chat = LLMChat::create([
            'messages' => [],
            'tokens' => 50,
        ]);

        $this->assertFalse($chat->hasMessages());
        $this->assertTrue($chat->isTokensCalculated());
        $this->assertEquals(50, $chat->getTokensOrZero());

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
        $chatWithTokens = new LLMChat(['tokens' => 100]);
        $this->assertTrue($chatWithTokens->isTokensCalculated());

        $chatWithZeroTokens = new LLMChat(['tokens' => 0]);
        $this->assertTrue($chatWithZeroTokens->isTokensCalculated());

        $chatWithoutTokens = new LLMChat(['tokens' => null]);
        $this->assertFalse($chatWithoutTokens->isTokensCalculated());
    }

    /** @test */
    public function get_tokens_or_zero_returns_correct_values()
    {
        $chatWithTokens = new LLMChat(['tokens' => 150]);
        $this->assertEquals(150, $chatWithTokens->getTokensOrZero());

        $chatWithZeroTokens = new LLMChat(['tokens' => 0]);
        $this->assertEquals(0, $chatWithZeroTokens->getTokensOrZero());

        $chatWithoutTokens = new LLMChat(['tokens' => null]);
        $this->assertEquals(0, $chatWithoutTokens->getTokensOrZero());
    }
}
