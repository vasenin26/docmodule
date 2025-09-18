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
        // Ensure the page has a current version before creating a draft
        $this->actingAs($user);
        $this->withoutMiddleware();
        $this->post(route('pages.store'), [
            'title' => 'Initial',
            'content' => 'Content',
            'parent_id' => null,
            'project_id' => null,
        ]);
        // Create draft from current version via controller method to maintain consistency
        $draft = $page->createDraft(['title' => 'Draft Title']);
        
        $response = $this->post(route('drafts.approve', $draft->id));
        
        $response->assertRedirect();
        
        // Проверяем, что Job был запущен при утверждении черновика
        // Ожидаем дифф между предыдущей версией и утверждаемым черновиком
        $previousVersionId = $draft->previous_version_id;
        Queue::assertPushed(CalculateVersionDifferenceJob::class, function ($job) use ($draft, $previousVersionId) {
            return $job->newVersionId === $draft->id && $job->oldVersionId === $previousVersionId;
        });
    }

    public function test_approve_draft_does_not_dispatch_job_for_non_draft()
    {
        Queue::fake();
        
        $user = User::factory()->create();
        $page = Page::factory()->create(['created_by' => $user->id]);
        $this->actingAs($user);
        $this->withoutMiddleware();
        
        $response = $this->post(route('drafts.approve', $page->id));
        
        $response->assertRedirect();
        
        // Проверяем, что Job НЕ был запущен для не-черновика
        Queue::assertNotPushed(CalculateVersionDifferenceJob::class);
    }
}
