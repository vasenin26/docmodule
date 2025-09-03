<?php

namespace App\Console\Commands;

use App\Interfaces\LLM\ContentGenerator;
use App\Models\AgentTask;
use Illuminate\Console\Command;

class TestCasting extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:casting';

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
        $a = AgentTask::create([
            'handler' => 'test',
            'handler_options' => ['option1' => 'value1', 'option2' => 'value2'],
            'project_id' => 25,
            'created_by' => 96,
            'chat_id' => 6,
            'status' => 'wait',
            'agent_id' => 1,
        ]);

        var_dump($a->handler_options['option1']);
    }
}
