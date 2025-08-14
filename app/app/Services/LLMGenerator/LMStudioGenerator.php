<?php

namespace App\Services\LLMGenerator;

use App\Common\DTO\LLMResultDTO;
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

    public function generate(string $prompt, string $systemPrompt = ''): LLMResultDTO
    {
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'system', 'content' => 'Сохрани результат в хранилище.'],
            ['role' => 'user', 'content' => $prompt],
        ];

        return $this->processMessages($messages);
    }

    public function processMessages(array $messages): LLMResultDTO
    {
        $client = OpenAI::factory()
            ->withApiKey('sk-proj-1234567890')
            ->withBaseUri('http://host.docker.internal:1234/v1')
            ->make();

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

            echo "agent: " . $lastMessage->content . "; tools: " . join(', ', array_map(fn($toolCall) => $toolCall->function->name, $toolCalls));
            echo "\n";

            if (empty($toolCalls)) {
                $messages[] = ['role' => 'assistant', 'content' => $lastMessage->content];
                $messages[] = ['role' => 'user', 'content' => 'Store answer with tools for finish'];
            } else {
                foreach ($toolCalls as $toolCall) {
                    $toolResult = $this->toolsFactory->callTool($toolCall->function->name, $toolCall->function->arguments);

                    if(is_array($toolResult)) {
                        $messages[] = [
                            'role' => 'tool',
                            'tool_call_id' => $toolCall->id,
                            'content' => 'Tools was call with wrong parameters'
                        ];

                        continue;
                    }

                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall->id,
                        'content' => json_encode($toolResult)
                    ];

                    if ($toolCall->function->name === ToolsFactory::RESULT_TOOL) {
                        $answer = $toolResult;
                    }
                }
            }

        } while (is_null($answer));

        echo count($messages);

        // Извлекаем детализированную информацию о токенах
        $promptTokens = null;
        $completionTokens = null; 
        $totalTokens = null;

        try {
            if (isset($result->usage)) {
                $promptTokens = $result->usage->promptTokens ?? null;
                $completionTokens = $result->usage->completionTokens ?? null;
                $totalTokens = $result->usage->totalTokens ?? null;
                
                // Логируем использование токенов для мониторинга расходов
                Log::info('OpenAI API usage', [
                    'prompt_tokens' => $promptTokens,
                    'completion_tokens' => $completionTokens,
                    'total_tokens' => $totalTokens,
                ]);
            } else {
                // Если информация о токенах недоступна, устанавливаем 0 для всех полей
                $promptTokens = 0;
                $completionTokens = 0;
                $totalTokens = 0;
                Log::warning('Информация о токенах недоступна в ответе OpenAI API');
            }
        } catch (\Exception $e) {
            // В случае ошибки устанавливаем 0 для всех полей
            $promptTokens = 0;
            $completionTokens = 0;
            $totalTokens = 0;
            Log::error('Ошибка при извлечении информации о токенах', [
                'error' => $e->getMessage()
            ]);
        }

        return new LLMResultDTO(
            $answer,
            $messages,
            $promptTokens,
            $completionTokens,
            $totalTokens
        );
    }
}
