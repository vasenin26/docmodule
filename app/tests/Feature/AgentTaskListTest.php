<?php

namespace Tests\Feature;

use App\Models\AgentTask;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentTaskListTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_scoped_agent_tasks_index_requires_auth(): void
    {
        $project = Project::factory()->create();

        $response = $this->get(route('projects.agent-tasks.index', $project->id));
        $response->assertRedirect('/login');
    }

    public function test_project_scoped_agent_tasks_index_renders_with_pagination(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);

        $this->actingAs($user);

        AgentTask::factory()->count(3)->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        $response = $this->get(route('projects.agent-tasks.index', $project->id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('agent-tasks/Index')
            ->has('tasks')
            ->has('tasks.data', 3)
            ->has('tasks.data.0', function ($task) {
                $task->has('id')
                     ->has('type')
                     ->has('creator')
                     ->has('chat_id')
                     ->has('context_id')
                     ->has('agent_model')
                     ->has('agent_assigned')
                     ->has('reserved_at')
                     ->has('reserved_until')
                     ->has('reserved_seconds')
                     ->has('status')
                     ->has('updated_at');
            })
            ->has('project')
            ->where('project.id', $project->id)
        );
    }

    public function test_non_owner_cannot_view_project_agent_tasks(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $other = User::factory()->create();
        $this->actingAs($other);

        $response = $this->get(route('projects.agent-tasks.index', $project->id));
        $response->assertStatus(403);
    }
}


