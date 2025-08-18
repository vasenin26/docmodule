<?php

namespace Tests\Feature\Unit\Models;

use App\Models\Page;
use App\Models\PageVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_page_version()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create(['created_by' => $user->id]);
        
        $version = PageVersion::factory()->create([
            'page_id' => $page->id,
            'title' => 'Test Title',
            'content' => 'Test Content',
        ]);

        $this->assertInstanceOf(PageVersion::class, $version);
        $this->assertEquals('Test Title', $version->title);
        $this->assertEquals('Test Content', $version->content);
        $this->assertEquals($page->id, $version->page_id);
    }

    public function test_page_version_has_page_relationship()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create(['created_by' => $user->id]);
        $version = PageVersion::factory()->create(['page_id' => $page->id]);

        $this->assertInstanceOf(Page::class, $version->page);
        $this->assertEquals($page->id, $version->page->id);
    }

    public function test_can_create_version_chain()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create(['created_by' => $user->id]);
        
        $version1 = PageVersion::factory()->create(['page_id' => $page->id]);
        $version2 = PageVersion::factory()->create([
            'page_id' => $page->id,
            'previous_version_id' => $version1->id,
        ]);

        $this->assertEquals($version1->id, $version2->previous_version_id);
        $this->assertInstanceOf(PageVersion::class, $version2->previousVersion);
        $this->assertEquals($version1->id, $version2->previousVersion->id);
    }

    public function test_can_create_new_version()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create(['created_by' => $user->id]);
        $version1 = PageVersion::factory()->create(['page_id' => $page->id]);

        $version2 = $version1->createNewVersion([
            'title' => 'Updated Title',
            'content' => 'Updated Content',
        ]);

        $this->assertInstanceOf(PageVersion::class, $version2);
        $this->assertEquals('Updated Title', $version2->title);
        $this->assertEquals('Updated Content', $version2->content);
        $this->assertEquals($version1->id, $version2->previous_version_id);
        $this->assertEquals($page->id, $version2->page_id);
    }

    public function test_files_attribute_returns_array()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create(['created_by' => $user->id]);
        
        $version = PageVersion::factory()->create([
            'page_id' => $page->id,
            'files' => ['file1.txt', 'file2.txt'],
        ]);

        $this->assertIsArray($version->files);
        $this->assertEquals(['file1.txt', 'file2.txt'], $version->files);
    }

    public function test_files_attribute_returns_empty_array_when_null()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create(['created_by' => $user->id]);
        
        $version = PageVersion::factory()->create([
            'page_id' => $page->id,
            'files' => null,
        ]);

        $this->assertIsArray($version->files);
        $this->assertEquals([], $version->files);
    }
}
