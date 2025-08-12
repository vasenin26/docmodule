<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\PageDiffDescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskStatusCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_check_generation_status()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create(['created_by' => $user->id]);
        
        $task = PageDiffDescription::create([
            'page_id' => $page->id,
            'content' => 'Test content',
            'created_by' => $user->id,
            'generation_status' => PageDiffDescription::STATUS_COMPLETED
        ]);

        $response = $this->actingAs($user)->getJson(route('tasks.status', $task));

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => PageDiffDescription::STATUS_COMPLETED,
                     'content' => 'Test content'
                 ]);
    }

    public function test_user_can_check_pending_generation_status()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create(['created_by' => $user->id]);
        
        $task = PageDiffDescription::create([
            'page_id' => $page->id,
            'content' => null,
            'created_by' => $user->id,
            'generation_status' => PageDiffDescription::STATUS_PENDING
        ]);

        $response = $this->actingAs($user)->getJson(route('tasks.status', $task));

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => PageDiffDescription::STATUS_PENDING,
                     'content' => null
                 ]);
    }

    public function test_user_can_check_generating_status()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create(['created_by' => $user->id]);
        
        $task = PageDiffDescription::create([
            'page_id' => $page->id,
            'content' => null,
            'created_by' => $user->id,
            'generation_status' => PageDiffDescription::STATUS_GENERATING
        ]);

        $response = $this->actingAs($user)->getJson(route('tasks.status', $task));

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => PageDiffDescription::STATUS_GENERATING,
                     'content' => null
                 ]);
    }

    public function test_user_can_check_failed_generation_status()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create(['created_by' => $user->id]);
        
        $task = PageDiffDescription::create([
            'page_id' => $page->id,
            'content' => null,
            'created_by' => $user->id,
            'generation_status' => PageDiffDescription::STATUS_FAILED
        ]);

        $response = $this->actingAs($user)->getJson(route('tasks.status', $task));

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => PageDiffDescription::STATUS_FAILED,
                     'content' => null
                 ]);
    }

    public function test_unauthenticated_user_cannot_check_status()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create(['created_by' => $user->id]);
        
        $task = PageDiffDescription::create([
            'page_id' => $page->id,
            'content' => 'Test content',
            'created_by' => $user->id,
            'generation_status' => PageDiffDescription::STATUS_COMPLETED
        ]);

        $response = $this->getJson(route('tasks.status', $task));

        $response->assertStatus(401);
    }

    public function test_response_includes_updated_at_timestamp()
    {
        $user = User::factory()->create();
        $page = Page::factory()->create(['created_by' => $user->id]);
        
        $task = PageDiffDescription::create([
            'page_id' => $page->id,
            'content' => 'Test content',
            'created_by' => $user->id,
            'generation_status' => PageDiffDescription::STATUS_COMPLETED
        ]);

        $response = $this->actingAs($user)->getJson(route('tasks.status', $task));

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'content',
                     'updated_at'
                 ]);
    }
}
