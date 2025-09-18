<?php

namespace Database\Factories;

use App\Common\Enums\AgentTaskType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgentTask>
 */
class AgentTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => AgentTaskType::TEXT,
            'handler' => 'App\\Services\\AgentTaskManager\\Handlers\\TestHandler',
            'handler_options' => ['test' => true],
            'project_id' => \App\Models\Project::factory(),
            'created_by' => \App\Models\User::factory(),
            'chat_id' => \App\Models\LLMChat::factory(),
            'status' => \App\Models\AgentTask::STATUS_WAIT,
            'agent_uuid' => null,
            'agent_id' => null,
            'result_required' => false,
        ];
    }
}
