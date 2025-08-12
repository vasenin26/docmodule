<?php

namespace App\Services\LLMGenerator;

use App\Interfaces\LLMGenerator;
use OpenAI;
use Illuminate\Support\Facades\Log;

class LMStudioGenerator implements LLMGenerator
{
    public function __construct(
        private ToolsFactory $toolsFactory
    )
    {
    }

    public function generate(string $prompt, string $systemPrompt = ''): string
    {
        $client = OpenAI::factory()
            ->withApiKey('sk-proj-1234567890')
            ->withBaseUri('http://host.docker.internal:1234/v1')
            ->make();

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $prompt],
        ];
        $processing = true;

        do {
            $answer = null;
            $result = $client->chat()->create([
                'model' => 'gpt-4o',
                'messages' => $messages,
                'tools' => $this->toolsFactory->getMeta()
            ]);

            $lastMessage = $result->choices[0]->message;
            $toolCalls = $lastMessage->toolCalls;

            $messages[] = (array)$lastMessage;

            var_dump((string)$lastMessage->content);

            if(empty($toolCalls)) {
                $answer = $lastMessage->content;
            } else {
                foreach ($toolCalls as $toolCall) {
                    $result = $this->toolsFactory->callTool($toolCall->function->name, $toolCall->function->arguments);

                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall->id,
                        'content' => json_encode($result)
                    ];
                }
            }

        } while (is_null($answer));

        var_dump($messages);

        // Логируем использование токенов для мониторинга расходов
        Log::info('OpenAI API usage', [
            'prompt_tokens' => $result->usage->promptTokens,
            'completion_tokens' => $result->usage->completionTokens,
            'total_tokens' => $result->usage->totalTokens,
        ]);

        return $result->choices[0]->message->content;
    }
}
