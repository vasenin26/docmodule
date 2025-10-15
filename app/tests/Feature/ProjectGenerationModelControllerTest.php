<?php

namespace Tests\Feature;

use App\Models\GenerationModel;
use App\Models\Project;
use App\Models\ProjectGenerationModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectGenerationModelControllerTest extends TestCase
{
    use RefreshDatabase;

    // API list endpoint removed: not required currently

    public function test_owner_can_view_index_and_manage_mappings(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user, 'owner')->create();
        $model = GenerationModel::factory()->create(['name' => 'gpt-4', 'context_size' => 8192]);

        $this->actingAs($user)
            ->get(route('projects.generation-models.index', $project))
            ->assertOk();

        // store mapping
        $this->post(route('projects.generation-models.store', $project), [
            'generation_type' => 'text',
            'model_id' => $model->id,
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('project_generation_models', [
            'project_id' => $project->id,
            'generation_type' => 'text',
            'model_id' => $model->id,
        ]);

        // show mapping
        $this->get(route('projects.generation-models.show', [$project, 'text']))
            ->assertOk()
            ->assertJson(['generation_type' => 'text', 'model_id' => $model->id]);

        // destroy mapping
        $this->delete(route('projects.generation-models.destroy', [$project, 'text']))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('project_generation_models', [
            'project_id' => $project->id,
            'generation_type' => 'text',
        ]);
    }

    public function test_non_owner_cannot_access(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();

        $this->actingAs($stranger)
            ->get(route('projects.generation-models.index', $project))
            ->assertForbidden();
    }
}


