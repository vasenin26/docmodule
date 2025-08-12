<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Interfaces\LLMGenerator;

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
    public function handle(LLMGenerator $llmGenerator)
    {
        return $llmGenerator->generate('Сколько времени?');
    }
}
