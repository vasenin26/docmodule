<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use OpenAI as OpenAIFactory;

class TestLMStudiConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-l-m-studi-connection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $client = OpenAIFactory::factory()
            ->withApiKey('sk-proj-1234567890')
            ->withBaseUri('http://host.docker.internal:1234/v1') 
            ->make();

        $result = $client->chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'user', 'content' => 'Привет! Расскажи мне про LLM'],
            ],
        ]);
        
        echo $result->choices[0]->message->content; // Hello! How can I assist you today?
            
    }
}
