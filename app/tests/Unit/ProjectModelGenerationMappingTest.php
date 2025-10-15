<?php

namespace Tests\Unit;

use App\Models\GenerationModel;
use App\Models\Project;
use App\Models\ProjectGenerationModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectModelGenerationMappingTest extends TestCase
{
    use RefreshDatabase;

    public function test_helper_methods_return_model_and_name(): void
    {
        $project = Project::factory()->create();
        $model = GenerationModel::factory()->create(['name' => 'gpt-4', 'context_size' => 8192]);

        ProjectGenerationModel::create([
            'project_id' => $project->id,
            'model_id' => $model->id,
            'generation_type' => 'text',
        ]);

        $found = $project->getGenerationModelForType('text');
        $this->assertNotNull($found);
        $this->assertEquals('gpt-4', $found->name);
        $this->assertEquals('gpt-4', $project->getGenerationModelNameForType('text'));
        $this->assertNull($project->getGenerationModelForType('code_nonexistent'));
    }
}


