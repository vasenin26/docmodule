<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\PageVersion;
use App\Models\User;
use App\Models\Actualization;
use App\Models\Project;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ActualizationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_start_actualization_for_page()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        $page = Page::factory()->create(['project_id' => $project->id, 'created_by' => $user->id]);

        $response = $this->actingAs($user)
            ->postJson(route('pages.actualize', $page));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('actualizations', [
            'page_id' => $page->id,
            'created_by' => $user->id,
            'status' => 'pending',
        ]);

        // Проверяем, что создан черновик
        $this->assertDatabaseHas('page_versions', [
            'page_id' => $page->id,
            'is_draft' => true,
        ]);

        // Проверяем, что актуализация привязана к черновику
        $draft = PageVersion::where('page_id', $page->id)
            ->where('is_draft', true)
            ->first();
        
        $this->assertDatabaseHas('actualizations', [
            'page_version_id' => $draft->id,
        ]);
    }

    public function test_can_start_actualization_for_specific_draft()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        $page = Page::factory()->create(['project_id' => $project->id, 'created_by' => $user->id]);
        
        // Создаем черновик вручную
        $draft = PageVersion::factory()->create([
            'page_id' => $page->id,
            'is_draft' => true,
            'title' => 'Test Draft'
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('drafts.actualize', $draft));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('actualizations', [
            'page_id' => $page->id,
            'page_version_id' => $draft->id,
            'created_by' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_cannot_start_multiple_actualizations_for_same_draft()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        $page = Page::factory()->create(['project_id' => $project->id, 'created_by' => $user->id]);
        
        // Создаем черновик
        $draft = PageVersion::factory()->create([
            'page_id' => $page->id,
            'is_draft' => true,
        ]);

        // Создаем первую актуализацию
        Actualization::create([
            'page_id' => $page->id,
            'page_version_id' => $draft->id,
            'status' => Actualization::STATUS_PENDING,
            'created_by' => $user->id,
        ]);

        // Попытка создать вторую актуализацию для того же черновика должна провалиться
        $response = $this->actingAs($user)
            ->postJson(route('drafts.actualize', $draft));

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_cannot_actualize_non_draft_version()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        $page = Page::factory()->create(['project_id' => $project->id, 'created_by' => $user->id]);
        
        // Создаем обычную версию (не черновик)
        $version = PageVersion::factory()->create([
            'page_id' => $page->id,
            'is_draft' => false,
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('drafts.actualize', $version));

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_actualization_creates_draft_from_current_version()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);
        
        // Создаем страницу с текущей версией
        $currentVersion = PageVersion::factory()->create([
            'title' => 'Current Version',
            'content' => 'Current content',
        ]);
        
        $page = Page::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
            'version_id' => $currentVersion->id,
        ]);
        
        $currentVersion->update(['page_id' => $page->id]);

        $response = $this->actingAs($user)
            ->postJson(route('pages.actualize', $page));

        $response->assertStatus(200);

        // Проверяем, что создан черновик на основе текущей версии
        $draft = PageVersion::where('page_id', $page->id)
            ->where('is_draft', true)
            ->first();
            
        $this->assertNotNull($draft);
        $this->assertEquals($currentVersion->id, $draft->previous_version_id);
        $this->assertEquals($currentVersion->title, $draft->title);
        $this->assertEquals($currentVersion->content, $draft->content);

        // Проверяем, что актуализация привязана к этому черновику
        $actualization = Actualization::where('page_id', $page->id)->first();
        $this->assertEquals($draft->id, $actualization->page_version_id);
    }
}
