<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentTask;
use App\Models\Project;
use App\Models\User;
use App\Services\AgentJwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AgentApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;
    private Agent $agent;
    private string $jwtToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаем пользователя и проект
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['owner_id' => $this->user->id]);

        // Создаем агента
        $this->agent = Agent::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Test Agent',
        ]);

        // Генерируем JWT токен
        $jwtService = app(AgentJwtService::class);
        $this->jwtToken = $jwtService->generateToken($this->agent);
        $this->agent->update(['token' => $this->jwtToken]);
    }

    #[Test] public function agent_can_get_task_with_valid_jwt_and_uuid()
    {
        // Создаем задачу для агента
        $task = AgentTask::factory()->create([
            'project_id' => $this->project->id,
            'status' => AgentTask::STATUS_WAIT,
        ]);

        $agentUuid = '550e8400-e29b-41d4-a716-446655440000';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->jwtToken,
        ])->postJson('/api/agent/task', [
            'agent_uuid' => $agentUuid,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'agent_uuid',
                'project_id',
                'agent_model',
                'chat' => [
                    'messages'
                ]
            ]);

        // Проверяем, что задача была назначена агенту
        $task->refresh();
        $this->assertEquals($agentUuid, $task->agent_uuid);
        $this->assertEquals($this->agent->id, $task->agent_id);
        $this->assertEquals(AgentTask::STATUS_PROCESSING, $task->status);
    }

    #[Test] public function agent_get_task_returns_preset_agent_model_when_available()
    {
        // Создаем задачу с предустановленной моделью
        $presetModel = 'gpt-4o';
        $task = AgentTask::factory()->create([
            'project_id' => $this->project->id,
            'status' => AgentTask::STATUS_WAIT,
            'agent_model' => $presetModel,
        ]);

        $agentUuid = '550e8400-e29b-41d4-a716-446655440000';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->jwtToken,
        ])->postJson('/api/agent/task', [
            'agent_uuid' => $agentUuid,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('agent_model', $presetModel);
    }

    #[Test] public function agent_cannot_get_task_without_jwt_token()
    {
        $response = $this->postJson('/api/agent/task', [
            'agent_uuid' => '550e8400-e29b-41d4-a716-446655440000',
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Token required']);
    }

    #[Test] public function agent_cannot_get_task_with_invalid_jwt_token()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
        ])->postJson('/api/agent/task', [
            'agent_uuid' => '550e8400-e29b-41d4-a716-446655440000',
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Invalid or expired token']);
    }

    #[Test] public function agent_cannot_get_task_without_agent_uuid()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->jwtToken,
        ])->postJson('/api/agent/task', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['agent_uuid']);
    }

    #[Test] public function agent_cannot_get_task_with_invalid_uuid_format()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->jwtToken,
        ])->postJson('/api/agent/task', [
            'agent_uuid' => 'invalid-uuid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['agent_uuid']);
    }

    #[Test] public function agent_can_update_task_with_correct_uuid()
    {
        // Создаем задачу, назначенную агенту
        $agentUuid = '550e8400-e29b-41d4-a716-446655440000';
        $task = AgentTask::factory()->create([
            'project_id' => $this->project->id,
            'agent_id' => $this->agent->id,
            'agent_uuid' => $agentUuid,
            'status' => AgentTask::STATUS_PROCESSING,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->jwtToken,
        ])->putJson("/api/agent/task/{$task->id}", [
            'agent_uuid' => $agentUuid,
            'stats' => [
                'prompt_tokens' => 100,
                'completion_tokens' => 50,
                'total_tokens' => 150,
            ],
            'result' => 'Task completed successfully',
            'completed' => true,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'updated',
                'message' => 'Task progress saved'
            ]);

        // Проверяем, что задача была обновлена
        $task->refresh();
        $this->assertEquals(AgentTask::STATUS_SUCCESS, $task->status);
    }

    #[Test] public function agent_cannot_update_task_with_wrong_uuid()
    {
        // Создаем задачу, назначенную агенту
        $agentUuid = '550e8400-e29b-41d4-a716-446655440000';
        $task = AgentTask::factory()->create([
            'project_id' => $this->project->id,
            'agent_id' => $this->agent->id,
            'agent_uuid' => $agentUuid,
            'status' => AgentTask::STATUS_PROCESSING,
        ]);

        $wrongUuid = '550e8400-e29b-41d4-a716-446655440001';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->jwtToken,
        ])->putJson("/api/agent/task/{$task->id}", [
            'agent_uuid' => $wrongUuid,
            'stats' => [
                'prompt_tokens' => 100,
                'completion_tokens' => 50,
                'total_tokens' => 150,
            ],
            'result' => 'Task completed successfully',
            'completed' => true,
        ]);

        $response->assertStatus(404)
            ->assertJson(['error' => 'Task not found, not assigned to this agent, or not in processing state']);
    }

    #[Test] public function agent_cannot_update_task_assigned_to_different_agent()
    {
        // Создаем другого агента
        $otherAgent = Agent::factory()->create([
            'project_id' => $this->project->id,
        ]);

        // Создаем задачу, назначенную другому агенту
        $agentUuid = '550e8400-e29b-41d4-a716-446655440000';
        $task = AgentTask::factory()->create([
            'project_id' => $this->project->id,
            'agent_id' => $otherAgent->id,
            'agent_uuid' => $agentUuid,
            'status' => AgentTask::STATUS_PROCESSING,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->jwtToken,
        ])->putJson("/api/agent/task/{$task->id}", [
            'agent_uuid' => $agentUuid,
            'stats' => [
                'prompt_tokens' => 100,
                'completion_tokens' => 50,
                'total_tokens' => 150,
            ],
            'result' => 'Task completed successfully',
            'completed' => true,
        ]);

        $response->assertStatus(404)
            ->assertJson(['error' => 'Task not found, not assigned to this agent, or not in processing state']);
    }
}
