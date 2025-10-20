<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentTask;
use App\Models\LLMChat;
use App\Models\Project;
use App\Models\User;
use App\Services\AgentJwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateSubtaskTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;
    private Agent $agent;
    private string $jwtToken;
    private AgentTask $parentTask;

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

        // Создаем родительскую задачу
        $this->parentTask = AgentTask::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->user->id,
            'agent_id' => $this->agent->id,
            'agent_uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'status' => AgentTask::STATUS_PROCESSING,
        ]);
    }

    #[Test]
    public function agent_can_create_subtask_with_valid_data()
    {
        $agentUuid = '550e8400-e29b-41d4-a716-446655440000';
        $subtaskType = 'custom_task_type';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->jwtToken,
        ])->postJson("/api/agent/task/{$this->parentTask->id}/subtasks", [
            'type' => $subtaskType,
            'agent_uuid' => $agentUuid,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['id']);

        // Проверяем, что подзадача создана
        $subtaskId = $response->json('id');
        $subtask = AgentTask::find($subtaskId);

        $this->assertNotNull($subtask);
        $this->assertEquals($subtaskType, $subtask->type);
        $this->assertEquals($this->parentTask->id, $subtask->parent_id);
        $this->assertEquals($this->project->id, $subtask->project_id);
        $this->assertEquals($this->user->id, $subtask->created_by);
        $this->assertEquals($this->agent->id, $subtask->agent_id);
        $this->assertEquals($agentUuid, $subtask->agent_uuid);
        $this->assertEquals(AgentTask::STATUS_WAIT, $subtask->status);
        $this->assertFalse($subtask->result_required);
        $this->assertNull($subtask->handler);
        $this->assertEquals([], $subtask->handler_options);

        // Проверяем, что создан новый чат
        $this->assertNotNull($subtask->chat_id);
        $chat = LLMChat::find($subtask->chat_id);
        $this->assertNotNull($chat);
        $this->assertEquals([], $chat->messages);
        $this->assertNull($chat->context_fill);
    }

    #[Test]
    public function agent_cannot_create_subtask_for_task_not_owned()
    {
        // Создаем другого агента
        $otherAgent = Agent::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Other Agent',
        ]);

        $otherJwtService = app(AgentJwtService::class);
        $otherJwtToken = $otherJwtService->generateToken($otherAgent);
        $otherAgent->update(['token' => $otherJwtToken]);

        $agentUuid = '550e8400-e29b-41d4-a716-446655440001';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $otherJwtToken,
        ])->postJson("/api/agent/task/{$this->parentTask->id}/subtasks", [
            'type' => 'custom_task_type',
            'agent_uuid' => $agentUuid,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'message' => 'Forbidden: parent task not owned by this agent',
            ]);
    }

    #[Test]
    public function agent_cannot_create_subtask_for_nonexistent_task()
    {
        $agentUuid = '550e8400-e29b-41d4-a716-446655440000';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->jwtToken,
        ])->postJson('/api/agent/task/99999/subtasks', [
            'type' => 'custom_task_type',
            'agent_uuid' => $agentUuid,
        ]);

        $response->assertStatus(404);
    }

    #[Test]
    public function agent_cannot_create_subtask_without_type()
    {
        $agentUuid = '550e8400-e29b-41d4-a716-446655440000';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->jwtToken,
        ])->postJson("/api/agent/task/{$this->parentTask->id}/subtasks", [
            'agent_uuid' => $agentUuid,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    #[Test]
    public function agent_cannot_create_subtask_with_empty_type()
    {
        $agentUuid = '550e8400-e29b-41d4-a716-446655440000';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->jwtToken,
        ])->postJson("/api/agent/task/{$this->parentTask->id}/subtasks", [
            'type' => '',
            'agent_uuid' => $agentUuid,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    #[Test]
    public function agent_cannot_create_subtask_with_too_long_type()
    {
        $agentUuid = '550e8400-e29b-41d4-a716-446655440000';
        $tooLongType = str_repeat('a', 256);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->jwtToken,
        ])->postJson("/api/agent/task/{$this->parentTask->id}/subtasks", [
            'type' => $tooLongType,
            'agent_uuid' => $agentUuid,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    #[Test]
    public function agent_cannot_create_subtask_without_authentication()
    {
        $agentUuid = '550e8400-e29b-41d4-a716-446655440000';

        $response = $this->postJson("/api/agent/task/{$this->parentTask->id}/subtasks", [
            'type' => 'custom_task_type',
            'agent_uuid' => $agentUuid,
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function agent_cannot_create_subtask_with_invalid_jwt()
    {
        $agentUuid = '550e8400-e29b-41d4-a716-446655440000';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid_token',
        ])->postJson("/api/agent/task/{$this->parentTask->id}/subtasks", [
            'type' => 'custom_task_type',
            'agent_uuid' => $agentUuid,
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function subtask_inherits_project_and_creator_from_parent()
    {
        $agentUuid = '550e8400-e29b-41d4-a716-446655440000';
        $subtaskType = 'inherited_task_type';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->jwtToken,
        ])->postJson("/api/agent/task/{$this->parentTask->id}/subtasks", [
            'type' => $subtaskType,
            'agent_uuid' => $agentUuid,
        ]);

        $response->assertStatus(201);

        $subtaskId = $response->json('id');
        $subtask = AgentTask::find($subtaskId);

        // Проверяем наследование от родительской задачи
        $this->assertEquals($this->parentTask->project_id, $subtask->project_id);
        $this->assertEquals($this->parentTask->created_by, $subtask->created_by);
        $this->assertEquals($this->parentTask->id, $subtask->parent_id);
    }

    #[Test]
    public function subtask_has_correct_default_values()
    {
        $agentUuid = '550e8400-e29b-41d4-a716-446655440000';
        $subtaskType = 'default_values_test';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->jwtToken,
        ])->postJson("/api/agent/task/{$this->parentTask->id}/subtasks", [
            'type' => $subtaskType,
            'agent_uuid' => $agentUuid,
        ]);

        $response->assertStatus(201);

        $subtaskId = $response->json('id');
        $subtask = AgentTask::find($subtaskId);

        // Проверяем значения по умолчанию
        $this->assertEquals(AgentTask::STATUS_WAIT, $subtask->status);
        $this->assertFalse($subtask->result_required);
        $this->assertNull($subtask->handler);
        $this->assertEquals([], $subtask->handler_options);
        $this->assertEquals($this->agent->id, $subtask->agent_id);
        $this->assertEquals($agentUuid, $subtask->agent_uuid);
    }
}
