<?php

namespace Tests\Unit\Models;

use App\Models\Actualization;
use App\Models\Page;
use App\Models\PageVersion;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ActualizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_actualization_can_be_created_with_draft()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create();
        $draft = PageVersion::factory()->create([
            'page_id' => $page->id,
            'is_draft' => true,
        ]);

        $actualization = Actualization::create([
            'page_id' => $page->id,
            'page_version_id' => $draft->id,
            'status' => Actualization::STATUS_PENDING,
            'created_by' => $user->id,
        ]);

        $this->assertTrue($actualization->validateDraftBinding());
        $this->assertEquals($draft->id, $actualization->page_version_id);
        $this->assertEquals($page->id, $actualization->page_id);
    }

    public function test_actualization_cannot_be_created_with_non_draft()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $user = User::factory()->create();
        $page = Page::factory()->create();
        $version = PageVersion::factory()->create([
            'page_id' => $page->id,
            'is_draft' => false,
        ]);

        Actualization::create([
            'page_id' => $page->id,
            'page_version_id' => $version->id,
            'status' => Actualization::STATUS_PENDING,
            'created_by' => $user->id,
        ]);
    }

    public function test_actualization_auto_sets_page_id_from_draft()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create();
        $draft = PageVersion::factory()->create([
            'page_id' => $page->id,
            'is_draft' => true,
        ]);

        $actualization = Actualization::create([
            'page_version_id' => $draft->id,
            'status' => Actualization::STATUS_PENDING,
            'created_by' => $user->id,
        ]);

        // page_id должен быть автоматически установлен из черновика
        $this->assertEquals($page->id, $actualization->page_id);
    }

    public function test_actualization_relationships()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create();
        $draft = PageVersion::factory()->create([
            'page_id' => $page->id,
            'is_draft' => true,
        ]);

        $actualization = Actualization::create([
            'page_id' => $page->id,
            'page_version_id' => $draft->id,
            'status' => Actualization::STATUS_PENDING,
            'created_by' => $user->id,
        ]);

        // Проверяем связи
        $this->assertEquals($page->id, $actualization->page->id);
        $this->assertEquals($draft->id, $actualization->pageVersion->id);
        $this->assertEquals($user->id, $actualization->createdBy->id);
    }
}
