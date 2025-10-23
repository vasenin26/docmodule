<?php

namespace Tests\Unit;

use App\Http\Resources\AgentTaskResource;
use App\Models\Agent;
use App\Models\AgentTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentTaskResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_assigned_true_when_both_agent_id_and_agent_uuid_present(): void
    {
        $agent = Agent::factory()->create();
        $task = AgentTask::factory()->create([
            'agent_id' => $agent->id,
            'agent_uuid' => 'uuid-1',
        ]);

        $resource = new AgentTaskResource($task);
        $data = $resource->toArray(request());

        $this->assertArrayHasKey('agent_assigned', $data);
        $this->assertTrue($data['agent_assigned']);
    }

    public function test_agent_assigned_false_when_one_of_fields_null(): void
    {
        $task = AgentTask::factory()->create([
            'agent_id' => null,
            'agent_uuid' => 'uuid-1',
        ]);

        $resource = new AgentTaskResource($task);
        $data = $resource->toArray(request());

        $this->assertArrayHasKey('agent_assigned', $data);
        $this->assertFalse($data['agent_assigned']);

        $agent = Agent::factory()->create();
        $task2 = AgentTask::factory()->create([
            'agent_id' => $agent->id,
            'agent_uuid' => null,
        ]);

        $resource2 = new AgentTaskResource($task2);
        $data2 = $resource2->toArray(request());
        $this->assertFalse($data2['agent_assigned']);
    }

    public function test_cost_is_null_when_task_cost_is_null(): void
    {
        $task = AgentTask::factory()->create([
            'cost' => null,
        ]);

        $resource = new AgentTaskResource($task);
        $data = $resource->toArray(request());

        $this->assertArrayHasKey('cost', $data);
        $this->assertNull($data['cost']);
    }

    public function test_cost_is_integer_when_task_cost_present(): void
    {
        // cost stored as integer RUB * 1000
        $task = AgentTask::factory()->create([
            'cost' => 1500000, // represents 1500.000 RUB stored as 1500*1000
        ]);

        $resource = new AgentTaskResource($task);
        $data = $resource->toArray(request());

        $this->assertArrayHasKey('cost', $data);
        $this->assertEquals(1500000, $data['cost']);
    }
}
