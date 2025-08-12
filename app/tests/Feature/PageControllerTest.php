<?php

namespace Tests\Feature;

use App\Jobs\CalculateVersionDifferenceJob;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_approve_draft_dispatches_task_creation_job()
    {
        Queue::fake();
        
        $user = User::factory()->create();
        $page = Page::factory()->create(['created_by' => $user->id]);
        $draft = $page->createDraft(['title' => 'Draft Title']);
        
        $this->actingAs($user);
        
        $response = $this->withoutMiddleware()->post(route('pages.draft.approve', $draft->id));
        
        $response->assertRedirect();
        
        // Проверяем, что Job был запущен при утверждении черновика
        Queue::assertPushed(CalculateVersionDifferenceJob::class, function ($job) use ($draft, $page) {
            return $job->newVersionId === $draft->id && $job->oldVersionId === $page->id;
        });
    }

    public function test_approve_draft_does_not_dispatch_job_for_non_draft()
    {
        Queue::fake();
        
        $user = User::factory()->create();
        $page = Page::factory()->create(['created_by' => $user->id]);
        
        $this->actingAs($user);
        
        $response = $this->withoutMiddleware()->post(route('pages.draft.approve', $page->id));
        
        $response->assertRedirect();
        
        // Проверяем, что Job НЕ был запущен для не-черновика
        Queue::assertNotPushed(CalculateVersionDifferenceJob::class);
    }
}
