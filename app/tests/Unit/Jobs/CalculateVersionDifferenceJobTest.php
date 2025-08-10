<?php

namespace Tests\Unit\Jobs;

use App\Jobs\CalculateVersionDifferenceJob;
use App\Jobs\GenerateTaskDescriptionJob;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CalculateVersionDifferenceJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_calculates_difference_for_new_page()
    {
        Queue::fake();

        $user = User::factory()->create();
        $page = Page::factory()->create([
            'title' => 'Test Page',
            'content' => 'Test content',
            'created_by' => $user->id,
        ]);

        $job = new CalculateVersionDifferenceJob($page->id, null);
        $job->handle();

        Queue::assertPushed(GenerateTaskDescriptionJob::class, function ($job) use ($page) {
            $differenceData = $job->differenceData;
            return $differenceData['new_version_id'] === $page->id &&
                   $differenceData['new_version_title'] === 'Test Page' &&
                   $differenceData['new_version_content'] === 'Test content' &&
                   $differenceData['is_new_page'] === true;
        });
    }

    public function test_job_calculates_difference_for_updated_page()
    {
        Queue::fake();

        $user = User::factory()->create();
        $oldPage = Page::factory()->create([
            'title' => 'Old Title',
            'content' => 'Old content',
            'created_by' => $user->id,
        ]);

        $newPage = Page::factory()->create([
            'title' => 'New Title',
            'content' => 'New content',
            'created_by' => $user->id,
            'previous_version_id' => $oldPage->id,
        ]);

        $job = new CalculateVersionDifferenceJob($newPage->id, $oldPage->id);
        $job->handle();

        Queue::assertPushed(GenerateTaskDescriptionJob::class, function ($job) use ($oldPage, $newPage) {
            $differenceData = $job->differenceData;
            return $differenceData['new_version_id'] === $newPage->id &&
                   $differenceData['old_version_id'] === $oldPage->id &&
                   $differenceData['title_changed'] === true &&
                   $differenceData['content_changed'] === true &&
                   $differenceData['is_new_page'] === false;
        });
    }

    public function test_job_handles_missing_old_version()
    {
        Queue::fake();

        $user = User::factory()->create();
        $page = Page::factory()->create([
            'title' => 'Test Page',
            'content' => 'Test content',
            'created_by' => $user->id,
        ]);

        $job = new CalculateVersionDifferenceJob($page->id, 999);
        $job->handle();

        Queue::assertPushed(GenerateTaskDescriptionJob::class, function ($job) {
            $differenceData = $job->differenceData;
            return $differenceData['is_new_page'] === true;
        });
    }
}
