<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\GenerationModel;
use App\Models\Project;
use App\Models\ProjectGenerationModel;
use App\Services\AgentJwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectGenerationModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_generation_models_returns_list()
    {
        $project = Project::create([
            'title' => 'Test Project',
            'owner_id' => 1,
            'public_key' => 'pk',
        ]);

        $agent = Agent::create([
            'name' => 'agent1',
            'token' => 'token',
            'project_id' => $project->id,
            'uuid' => 'uuid1',
            'public_key' => 'pk',
            'has_cross_project_access' => false,
        ]);

        $gm1 = GenerationModel::create(['name' => 'TextGenV1', 'context_size' => 1024, 'price_in' => 0, 'price_out' => 0]);
        $gm2 = GenerationModel::create(['name' => 'ImageGenV1', 'context_size' => 0, 'price_in' => 0, 'price_out' => 0]);

        ProjectGenerationModel::create([
            'project_id' => $project->id,
            'model_id' => $gm1->id,
            'generation_type' => 'text',
        ]);

        ProjectGenerationModel::create([
            'project_id' => $project->id,
            'model_id' => $gm2->id,
            'generation_type' => 'image',
        ]);

        // Bind AgentJwtService to return our agent for the token
        $this->app->bind(AgentJwtService::class, function () use ($agent) {
            return new class($agent) {
                private $agent;
                public function __construct($agent) { $this->agent = $agent; }
                public function validateToken($token) { return $this->agent; }
            };
        });

        $response = $this->withHeaders(['Authorization' => 'Bearer token'])
            ->getJson("/api/project/{$project->id}/generation-models");

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'TextGenV1', 'generation_type' => 'text'])
            ->assertJsonFragment(['name' => 'ImageGenV1', 'generation_type' => 'image']);
    }

    public function test_returns_403_when_agent_no_access()
    {
        $project = Project::create([
            'title' => 'Test Project 2',
            'owner_id' => 1,
            'public_key' => 'pk',
        ]);

        $otherProject = Project::create([
            'title' => 'Other Project',
            'owner_id' => 2,
            'public_key' => 'pk2',
        ]);

        $agent = Agent::create([
            'name' => 'agent2',
            'token' => 'token2',
            'project_id' => $otherProject->id,
            'uuid' => 'uuid2',
            'public_key' => 'pk2',
            'has_cross_project_access' => false,
        ]);

        $this->app->bind(AgentJwtService::class, function () use ($agent) {
            return new class($agent) {
                private $agent;
                public function __construct($agent) { $this->agent = $agent; }
                public function validateToken($token) { return $this->agent; }
            };
        });

        $response = $this->withHeaders(['Authorization' => 'Bearer token2'])
            ->getJson("/api/project/{$project->id}/generation-models");

        $response->assertStatus(403);
    }

    public function test_returns_404_for_missing_project()
    {
        $agent = Agent::create([
            'name' => 'agent3',
            'token' => 'token3',
            'project_id' => 9999,
            'uuid' => 'uuid3',
            'public_key' => 'pk3',
            'has_cross_project_access' => false,
        ]);

        $this->app->bind(AgentJwtService::class, function () use ($agent) {
            return new class($agent) {
                private $agent;
                public function __construct($agent) { $this->agent = $agent; }
                public function validateToken($token) { return $this->agent; }
            };
        });

        $response = $this->withHeaders(['Authorization' => 'Bearer token3'])
            ->getJson('/api/project/99999/generation-models');

        $response->assertStatus(404);
    }

    public function test_returns_empty_array_when_no_mappings()
    {
        $project = Project::create([
            'title' => 'Empty Project',
            'owner_id' => 1,
            'public_key' => 'pk',
        ]);

        $agent = Agent::create([
            'name' => 'agent4',
            'token' => 'token4',
            'project_id' => $project->id,
            'uuid' => 'uuid4',
            'public_key' => 'pk4',
            'has_cross_project_access' => false,
        ]);

        $this->app->bind(AgentJwtService::class, function () use ($agent) {
            return new class($agent) {
                private $agent;
                public function __construct($agent) { $this->agent = $agent; }
                public function validateToken($token) { return $this->agent; }
            };
        });

        $response = $this->withHeaders(['Authorization' => 'Bearer token4'])
            ->getJson("/api/project/{$project->id}/generation-models");

        $response->assertStatus(200)
            ->assertExactJson([]);
    }
}
