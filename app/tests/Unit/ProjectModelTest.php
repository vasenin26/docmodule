<?php

namespace Tests\Unit;

use App\Models\Page;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_belongs_to_owner(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user, 'owner')->create();

        $this->assertInstanceOf(User::class, $project->owner);
        $this->assertEquals($user->id, $project->owner->id);
    }

    public function test_project_has_many_pages(): void
    {
        $project = Project::factory()->create();
        $pages = Page::factory(3)->for($project)->create();

        $this->assertCount(3, $project->pages);
        $this->assertInstanceOf(Page::class, $project->pages->first());
    }

    public function test_project_fillable_attributes(): void
    {
        $project = new Project();
        $fillable = $project->getFillable();

        $this->assertContains('title', $fillable);
        $this->assertContains('owner_id', $fillable);
    }

    public function test_project_can_be_created_with_factory(): void
    {
        $project = Project::factory()->create();

        $this->assertInstanceOf(Project::class, $project);
        $this->assertNotNull($project->title);
        $this->assertNotNull($project->owner_id);
    }

    public function test_project_creation_with_specific_data(): void
    {
        $user = User::factory()->create();
        $projectData = [
            'title' => 'Test Project',
            'owner_id' => $user->id,
        ];

        $project = Project::create($projectData);

        $this->assertEquals('Test Project', $project->title);
        $this->assertEquals($user->id, $project->owner_id);
    }
}
