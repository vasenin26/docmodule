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
        $project = Project::factory()->create();

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
            ->has('project')
            ->where('project.id', $project->id)
        );
    }
}


