<?php

namespace App\Services\LLMGenerator;

use App\Interfaces\LLMGenerator;
use OpenAI;
use Illuminate\Support\Facades\Log;

class LMStudioGenerator implements LLMGenerator
{
    public function generate(string $prompt, string $systemPrompt = ''): string
    {
        $client = OpenAI::factory()
            ->withApiKey('sk-proj-1234567890')
            ->withBaseUri('http://host.docker.internal:1234/v1') 
            ->make();

        $result = $client->chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        
        // Логируем использование токенов для мониторинга расходов
        Log::info('OpenAI API usage', [
            'prompt_tokens' => $result->usage->promptTokens,
            'completion_tokens' => $result->usage->completionTokens,
            'total_tokens' => $result->usage->totalTokens,
        ]);
        
        return $result->choices[0]->message->content; 
    }
}