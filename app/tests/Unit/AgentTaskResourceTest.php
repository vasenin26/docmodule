<?php

namespace Tests\Unit;

use App\Http\Resources\AgentTaskResource;
use App\Models\AgentTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentTaskResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_assigned_true_when_both_agent_id_and_agent_uuid_present(): void
    {
        $task = AgentTask::factory()->create([
            'agent_id' => 123,
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

        $task2 = AgentTask::factory()->create([
            'agent_id' => 2,
            'agent_uuid' => null,
        ]);

        $resource2 = new AgentTaskResource($task2);
        $data2 = $resource2->toArray(request());
        $this->assertFalse($data2['agent_assigned']);
    }
}


