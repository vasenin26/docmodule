<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DraftModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_draft_basic_functionality()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create(['created_by' => $user->id]);

        // Создаем черновик
        $draft = $page->createDraft(['title' => 'Draft Title']);

        // Проверяем, что черновик создан
        $this->assertNotNull($draft);
        $this->assertEquals('Draft Title', $draft->title);
        $this->assertFalse($draft->current);
        $this->assertEquals($page->id, $draft->previous_version_id);
        $this->assertEquals($page->base_id ?? $page->id, $draft->base_id);
    }

    public function test_get_current_draft_after_creation()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create(['created_by' => $user->id]);

        // Создаем черновик
        $draft = $page->createDraft(['title' => 'Draft Title']);

        // Обновляем страницу
        $page->refresh();

        // Проверяем, что черновик найден
        $currentDraft = $page->getCurrentDraft();
        $this->assertNotNull($currentDraft);
        $this->assertEquals($draft->id, $currentDraft->id);
    }

    public function test_has_active_draft_after_creation()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create(['created_by' => $user->id]);

        // Проверяем, что изначально нет черновика
        $this->assertFalse($page->hasActiveDraft());

        // Создаем черновик
        $draft = $page->createDraft(['title' => 'Draft Title']);

        // Обновляем страницу
        $page->refresh();

        // Проверяем, что теперь есть черновик
        $this->assertTrue($page->hasActiveDraft());
    }

    public function test_approve_draft_functionality()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create(['created_by' => $user->id]);

        // Создаем черновик
        $draft = $page->createDraft(['title' => 'Draft Title']);

        // Утверждаем черновик
        $draft->approveDraft();

        // Обновляем модели
        $draft->refresh();
        $page->refresh();

        // Проверяем, что черновик стал текущей версией
        $this->assertTrue($draft->current);
        $this->assertFalse($page->current);
    }

    public function test_is_draft_method()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create(['created_by' => $user->id]);

        // Проверяем, что оригинальная страница не является черновиком
        $this->assertFalse($page->isDraft());

        // Создаем черновик
        $draft = $page->createDraft(['title' => 'Draft Title']);

        // Проверяем, что черновик определяется как черновик
        $this->assertTrue($draft->isDraft());
    }
}
