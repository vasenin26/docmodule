<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DraftFunctionalityTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_draft()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)
            ->withoutMiddleware()
            ->put(route('pages.update', $page->id), [
                'title' => 'Updated Title',
                'content' => 'Updated content'
            ]);

        $response->assertRedirect();
        
        // Обновляем страницу и ищем черновик
        $page->refresh();
        $draft = Page::where('base_id', $page->base_id)
            ->where('current', false)
            ->where('id', '!=', $page->id)
            ->first();
            
        $this->assertNotNull($draft);
        $this->assertEquals('Updated Title', $draft->title);
        $this->assertFalse($draft->current);
    }

    public function test_can_approve_draft()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create(['created_by' => $user->id]);
        $draft = $page->createDraft(['title' => 'Draft Title']);

        $response = $this->actingAs($user)
            ->withoutMiddleware()
            ->post(route('pages.draft.approve', $draft->id));

        $response->assertRedirect(route('pages.show', $draft->id));
        
        // Обновляем модели из базы данных
        $draft->refresh();
        $page->refresh();
        
        // Проверяем, что черновик стал текущей версией
        $this->assertTrue($draft->current, "Draft should be current after approval");
        $this->assertFalse($page->current, "Original page should not be current after approval");
    }

    public function test_can_continue_existing_draft()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create(['created_by' => $user->id]);
        $draft = $page->createDraft(['title' => 'Original Draft']);

        $response = $this->actingAs($user)
            ->withoutMiddleware()
            ->put(route('pages.update', $page->id), [
                'title' => 'Updated Draft',
                'content' => 'Updated content'
            ]);

        $response->assertRedirect();
        
        // Находим обновленный черновик и проверяем
        $updatedDraft = Page::where('base_id', $page->base_id)
            ->where('current', false)
            ->where('id', '!=', $page->id)
            ->first();
        $this->assertEquals('Updated Draft', $updatedDraft->title);
    }

    public function test_can_delete_draft()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create(['created_by' => $user->id]);
        $draft = $page->createDraft(['title' => 'Draft Title']);

        $response = $this->actingAs($user)
            ->withoutMiddleware()
            ->delete(route('pages.draft.delete', $page->id));

        $response->assertRedirect();
        
        $page->refresh();
        $draftExists = Page::where('base_id', $page->base_id)
            ->where('current', false)
            ->where('id', '!=', $page->id)
            ->exists();
        $this->assertFalse($draftExists);
    }

    public function test_draft_is_not_current_version()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create(['created_by' => $user->id]);
        $draft = $page->createDraft(['title' => 'Draft Title']);

        $this->assertFalse($draft->current);
        $this->assertTrue($page->current);
    }

    public function test_approving_draft_makes_it_current()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create(['created_by' => $user->id]);
        $draft = $page->createDraft(['title' => 'Draft Title']);

        $draft->approveDraft();

        $draft->refresh();
        $page->refresh();

        $this->assertTrue($draft->current);
        $this->assertFalse($page->current);
    }

    public function test_has_active_draft_detection()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create(['created_by' => $user->id]);

        $this->assertFalse($page->hasActiveDraft());

        $draft = $page->createDraft(['title' => 'Draft Title']);
        
        $page->refresh();
        $this->assertTrue($page->hasActiveDraft());
    }

    public function test_get_current_draft_returns_latest_draft()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create(['created_by' => $user->id]);

        $draft1 = $page->createDraft(['title' => 'First Draft']);
        sleep(1); // Добавляем задержку для разных дат
        $draft2 = $page->createDraft(['title' => 'Second Draft']);

        $page->refresh();
        $currentDraft = $page->getCurrentDraft();

        $this->assertNotNull($currentDraft);
        // Проверяем, что получен черновик с более поздней датой создания
        $this->assertGreaterThan($draft1->created_at, $currentDraft->created_at);
    }
}
