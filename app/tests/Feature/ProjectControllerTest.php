<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_guest_cannot_access_projects(): void
    {
        $response = $this->get(route('projects.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_user_can_view_projects_index(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('projects.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('projects/Index')
        );
    }

    public function test_user_can_create_project(): void
    {
        $this->actingAs($this->user);

        $projectData = [
            'title' => 'Test Project',
        ];

        $response = $this->post(route('projects.store'), $projectData);

        $this->assertDatabaseHas('projects', [
            'title' => 'Test Project',
            'owner_id' => $this->user->id,
        ]);

        $project = Project::where('title', 'Test Project')->first();
        $response->assertRedirect(route('projects.show', $project));
    }

    public function test_project_title_is_required(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('projects.store'), []);

        $response->assertSessionHasErrors(['title']);
    }

    public function test_user_can_view_own_project(): void
    {
        $this->actingAs($this->user);

        $project = Project::factory()->for($this->user, 'owner')->create();

        $response = $this->get(route('projects.show', $project));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('projects/Show')
                ->has('project')
                ->where('project.id', $project->id)
        );
    }

    public function test_user_cannot_view_other_users_project(): void
    {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $project = Project::factory()->for($otherUser, 'owner')->create();

        $response = $this->get(route('projects.show', $project));

        $response->assertStatus(403);
    }

    public function test_user_can_update_own_project(): void
    {
        $this->actingAs($this->user);

        $project = Project::factory()->for($this->user, 'owner')->create();

        $updateData = [
            'title' => 'Updated Project Title',
        ];

        $response = $this->put(route('projects.update', $project), $updateData);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'title' => 'Updated Project Title',
        ]);

        $response->assertRedirect(route('projects.show', $project));
    }

    public function test_user_cannot_update_other_users_project(): void
    {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $project = Project::factory()->for($otherUser, 'owner')->create();

        $response = $this->put(route('projects.update', $project), [
            'title' => 'Updated Title',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_own_project(): void
    {
        $this->actingAs($this->user);

        $project = Project::factory()->for($this->user, 'owner')->create();

        $response = $this->delete(route('projects.destroy', $project));

        $this->assertDatabaseMissing('projects', [
            'id' => $project->id,
        ]);

        $response->assertRedirect(route('projects.index'));
    }

    public function test_user_cannot_delete_other_users_project(): void
    {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $project = Project::factory()->for($otherUser, 'owner')->create();

        $response = $this->delete(route('projects.destroy', $project));

        $response->assertStatus(403);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
        ]);
    }
}
