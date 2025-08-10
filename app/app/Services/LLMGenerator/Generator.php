<?php

class Generator implements LLMGenerator
{
    public function generate(string $prompt): string;
    {
        $client = OpenAIFactory::factory()
            ->withApiKey('sk-proj-1234567890')
            ->withBaseUri('http://host.docker.internal:1234/v1') 
            ->make();

        $result = $client->chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);
        
        echo $result->choices[0]->message->content; 
            
    }
}