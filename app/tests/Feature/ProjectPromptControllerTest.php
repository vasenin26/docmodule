<?php

namespace Tests\Feature;

use App\Common\Enums\PromptType;
use App\Models\Project;
use App\Models\Prompt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectPromptControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->for($this->user, 'owner')->create();
    }

    public function test_index_displays_prompts_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('projects.prompts.index', $this->project));

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->component('projects/Prompts')
                ->has('project')
                ->has('prompts')
                ->has('prompt_types')
        );
    }

    public function test_unauthorized_user_cannot_access_prompts(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->get(route('projects.prompts.index', $this->project));

        $response->assertForbidden();
    }

    public function test_destroy_deletes_prompt(): void
    {
        $prompt = Prompt::factory()->create([
            'project_id' => $this->project->id,
            'type' => PromptType::TASK_MANAGER->value,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson(route('projects.prompts.destroy', [
                'project' => $this->project,
                'type' => PromptType::TASK_MANAGER->value
            ]));

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('prompts', ['id' => $prompt->id]);
    }

    public function test_preview_returns_rendered_content(): void
    {
        $data = [
            'type' => PromptType::TASK_DESCRIPTION->value,
            'content' => 'Test {{diff}} content',
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('projects.prompts.preview', $this->project), $data);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure(['rendered_content']);
    }

    public function test_store_validates_prompt_type(): void
    {
        $data = [
            'type' => 'invalid-type',
            'content' => 'Test content',
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('projects.prompts.store', $this->project), $data);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['type']);
    }

    public function test_store_validates_content_required(): void
    {
        $data = [
            'type' => PromptType::TASK_MANAGER->value,
            'content' => '',
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('projects.prompts.store', $this->project), $data);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['content']);
    }
}
