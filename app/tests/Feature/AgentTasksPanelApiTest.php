<?php

namespace Tests\Feature;

use App\Models\AgentTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentTasksPanelApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_check_returns_requested_and_active_tasks_only_for_user()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $active = AgentTask::factory()->create([
            'created_by' => $user->id,
            'status' => AgentTask::STATUS_PROCESSING,
        ]);

        $requested = AgentTask::factory()->create([
            'created_by' => $user->id,
            'status' => AgentTask::STATUS_SUCCESS,
        ]);

        // Another user's task should not be returned
        $other = User::factory()->create();
        $foreign = AgentTask::factory()->create([
            'created_by' => $other->id,
            'status' => AgentTask::STATUS_PROCESSING,
        ]);

        $response = $this->postJson(route('agent-tasks.check'), ['ids' => [(string) $requested->id]]);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $ids = collect($response->json('data'))->pluck('id')->map(fn($v) => (string) $v)->all();
        $this->assertContains((string) $active->id, $ids);
        $this->assertContains((string) $requested->id, $ids);
        $this->assertNotContains((string) $foreign->id, $ids);
    }

    public function test_post_check_with_empty_ids_returns_only_active_tasks()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $active = AgentTask::factory()->create([
            'created_by' => $user->id,
            'status' => AgentTask::STATUS_WAIT,
        ]);

        $inactive = AgentTask::factory()->create([
            'created_by' => $user->id,
            'status' => AgentTask::STATUS_SUCCESS,
        ]);

        $response = $this->postJson(route('agent-tasks.check'), ['ids' => []]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $ids = collect($response->json('data'))->pluck('id')->map(fn($v) => (string) $v)->all();
        $this->assertContains((string) $active->id, $ids);
        $this->assertNotContains((string) $inactive->id, $ids);
    }
}
