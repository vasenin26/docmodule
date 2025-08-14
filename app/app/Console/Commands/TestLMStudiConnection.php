<?php

namespace App\Console\Commands;

use App\Interfaces\LLM\ContentGenerator;
use Illuminate\Console\Command;

class TestLMStudiConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:llm';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(ContentGenerator $llmGenerator)
    {
        $result = $llmGenerator->generate('Прочитай файл https://github.com/vasenin26/savemyass/blob/master/Makefile и верни содержимое');

        foreach ($result->messages as $message) {
            echo "----- " .$message['role'] ." -----\n";

            if(!empty($message['toolCalls'])) {
                foreach ($message['toolCalls'] as $toolCall) {
                    echo 'Call ' . $toolCall->function->name . " with " .$toolCall->function->arguments. "\n";
                }
            }

            if(!empty($message['content'])) {
                echo 'Message: ' . $message['content'] . "\n";
            }
        }

        echo "Answer:" . $result->answer . "\n";
    }
}
