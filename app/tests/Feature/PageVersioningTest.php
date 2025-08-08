<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageVersioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_creation_with_correct_versioning_fields()
    {
        $user = User::factory()->create();

        $page = Page::create([
            'title' => 'Test Page',
            'content' => 'Test content',
            'created_by' => $user->id,
            'current' => true,
        ]);

        $this->assertNull($page->base_id);
        $this->assertNull($page->previous_version_id);
        $this->assertTrue($page->current);
    }

    public function test_page_version_creation()
    {
        $user = User::factory()->create();

        // Создаем первую версию
        $originalPage = Page::create([
            'title' => 'Original Page',
            'content' => 'Original content',
            'created_by' => $user->id,
            'current' => true,
        ]);

        // Создаем новую версию
        $newVersion = $originalPage->createNewVersion([
            'title' => 'Updated Page',
            'content' => 'Updated content',
        ]);

        $this->assertEquals($originalPage->id, $newVersion->base_id);
        $this->assertEquals($originalPage->id, $newVersion->previous_version_id);
        $this->assertTrue($newVersion->current);
        $this->assertFalse($originalPage->fresh()->current);
    }

    public function test_version_chain_retrieval()
    {
        $user = User::factory()->create();

        // Создаем цепочку версий
        $page1 = Page::create([
            'title' => 'Version 1',
            'content' => 'Content 1',
            'created_by' => $user->id,
            'current' => false,
        ]);

        $page2 = $page1->createNewVersion([
            'title' => 'Version 2',
            'content' => 'Content 2',
        ]);

        $page3 = $page2->createNewVersion([
            'title' => 'Version 3',
            'content' => 'Content 3',
        ]);

        // Получаем цепочку версий
        $chain = $page3->getVersionChain();

        $this->assertEquals(3, $chain->count());
        $this->assertEquals('Version 1', $chain->first()->title);
        $this->assertEquals('Version 3', $chain->last()->title);
    }

    public function test_previous_and_next_version_relationships()
    {
        $user = User::factory()->create();

        $page1 = Page::create([
            'title' => 'Version 1',
            'content' => 'Content 1',
            'created_by' => $user->id,
            'current' => false,
        ]);

        $page2 = $page1->createNewVersion([
            'title' => 'Version 2',
            'content' => 'Content 2',
        ]);

        // Проверяем связи
        $this->assertNull($page1->previousVersion);
        $this->assertEquals($page2->id, $page1->nextVersion->first()->id);
        $this->assertEquals($page1->id, $page2->previousVersion->id);
        $this->assertTrue($page2->nextVersion->isEmpty());
    }
}
