<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Page;
use App\Models\PageVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PageEditingProcessTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_current_version_shows_create_draft_button()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)
            ->get(route('pages.edit', $page));

        $response->assertInertia(fn($page) => $page
            ->component('pages/Edit')
            ->has('is_current_version')
        );
    }

    public function test_create_draft_from_current_version()
    {
        $user = User::factory()->create();
        $page = Page::factory()->withVersions()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)
            ->post(route('pages.create-draft', $page), [
                'title' => 'New Draft Title',
                'content' => 'New draft content',
                'files' => [],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Черновик создан.');

        $this->assertDatabaseHas('page_versions', [
            'page_id' => $page->id,
            'title' => 'New Draft Title',
            'content' => 'New draft content',
            'is_draft' => true,
        ]);
    }

    public function test_cannot_update_current_version_directly()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)
            ->put(route('pages.update', $page), [
                'title' => 'Updated Title',
                'content' => 'Updated content',
            ]);

        $response->assertForbidden();
    }

    public function test_continue_editing_existing_draft()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create(['created_by' => $user->id]);

        // Создаем черновик
        $draft = PageVersion::factory()->create([
            'page_id' => $page->id,
            'is_draft' => true,
            'title' => 'Draft Title',
            'content' => 'Draft content',
        ]);

        $response = $this->actingAs($user)
            ->put(route('pages.versions.update', [$page->id, $draft->id]), [
                'title' => 'Updated Draft Title',
                'content' => 'Updated draft content',
                'files' => [],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Черновик обновлен.');

        $this->assertDatabaseHas('page_versions', [
            'id' => $draft->id,
            'title' => 'Updated Draft Title',
            'content' => 'Updated draft content',
        ]);
    }
}
