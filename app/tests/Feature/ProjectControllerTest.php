<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\GenerationModel;
use App\Models\Project;
use App\Models\ProjectGenerationModel;
use App\Models\User;
use App\Services\AgentJwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['owner_id' => $this->user->id]);
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

    // API Tests for generationModels method

    #[Test]
    public function agent_can_get_generation_models_for_own_project(): void
    {
        // Создаем агента для проекта
        $agent = Agent::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Test Agent',
        ]);

        // Создаем модели генерации
        $model1 = GenerationModel::factory()->create(['name' => 'gpt-4']);
        $model2 = GenerationModel::factory()->create(['name' => 'gpt-3.5-turbo']);

        // Создаем связи между проектом и моделями
        ProjectGenerationModel::create([
            'project_id' => $this->project->id,
            'model_id' => $model1->id,
            'generation_type' => 'completion',
        ]);

        ProjectGenerationModel::create([
            'project_id' => $this->project->id,
            'model_id' => $model2->id,
            'generation_type' => 'chat',
        ]);

        // Генерируем JWT токен для агента
        $jwtService = app(AgentJwtService::class);
        $jwtToken = $jwtService->generateToken($agent);
        $agent->update(['token' => $jwtToken]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $jwtToken,
        ])->getJson("/api/project/{$this->project->id}/generation-models");

        $response->assertStatus(200)
            ->assertJsonCount(2);
            
        $responseData = $response->json();
        $this->assertContains([
            'name' => 'gpt-4',
            'generation_type' => 'completion',
        ], $responseData);
        $this->assertContains([
            'name' => 'gpt-3.5-turbo',
            'generation_type' => 'chat',
        ], $responseData);
    }

    #[Test]
    public function agent_cannot_get_generation_models_for_other_project(): void
    {
        // Создаем другой проект и агента
        $otherUser = User::factory()->create();
        $otherProject = Project::factory()->create(['owner_id' => $otherUser->id]);
        $otherAgent = Agent::factory()->create([
            'project_id' => $otherProject->id,
            'name' => 'Other Agent',
        ]);

        // Создаем агента для нашего проекта
        $agent = Agent::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Test Agent',
        ]);

        // Генерируем JWT токен для нашего агента
        $jwtService = app(AgentJwtService::class);
        $jwtToken = $jwtService->generateToken($agent);
        $agent->update(['token' => $jwtToken]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $jwtToken,
        ])->getJson("/api/project/{$otherProject->id}/generation-models");

        $response->assertStatus(403);
    }

    #[Test]
    public function agent_with_cross_project_access_can_get_generation_models(): void
    {
        // Создаем агента с глобальным доступом
        $agent = Agent::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Global Agent',
            'has_cross_project_access' => true,
        ]);

        // Создаем модель генерации для проекта
        $model = GenerationModel::factory()->create(['name' => 'gpt-4']);
        ProjectGenerationModel::create([
            'project_id' => $this->project->id,
            'model_id' => $model->id,
            'generation_type' => 'completion',
        ]);

        // Генерируем JWT токен
        $jwtService = app(AgentJwtService::class);
        $jwtToken = $jwtService->generateToken($agent);
        $agent->update(['token' => $jwtToken]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $jwtToken,
        ])->getJson("/api/project/{$this->project->id}/generation-models");

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJson([
                [
                    'name' => 'gpt-4',
                    'generation_type' => 'completion',
                ],
            ]);
    }

    #[Test]
    public function generation_models_returns_empty_array_when_no_models(): void
    {
        // Создаем агента для проекта
        $agent = Agent::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Test Agent',
        ]);

        // Генерируем JWT токен
        $jwtService = app(AgentJwtService::class);
        $jwtToken = $jwtService->generateToken($agent);
        $agent->update(['token' => $jwtToken]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $jwtToken,
        ])->getJson("/api/project/{$this->project->id}/generation-models");

        $response->assertStatus(200)
            ->assertJsonCount(0)
            ->assertJson([]);
    }

    #[Test]
    public function generation_models_requires_authentication(): void
    {
        $response = $this->getJson("/api/project/{$this->project->id}/generation-models");

        $response->assertStatus(401);
    }

    #[Test]
    public function generation_models_returns_correct_format(): void
    {
        // Создаем агента для проекта
        $agent = Agent::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Test Agent',
        ]);

        // Создаем модель генерации
        $model = GenerationModel::factory()->create([
            'name' => 'claude-3-opus',
            'context_size' => 200000,
        ]);

        ProjectGenerationModel::create([
            'project_id' => $this->project->id,
            'model_id' => $model->id,
            'generation_type' => 'embedding',
        ]);

        // Генерируем JWT токен
        $jwtService = app(AgentJwtService::class);
        $jwtToken = $jwtService->generateToken($agent);
        $agent->update(['token' => $jwtToken]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $jwtToken,
        ])->getJson("/api/project/{$this->project->id}/generation-models");

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'name',
                    'generation_type',
                ],
            ])
            ->assertJson([
                [
                    'name' => 'claude-3-opus',
                    'generation_type' => 'embedding',
                ],
            ]);
    }
}
