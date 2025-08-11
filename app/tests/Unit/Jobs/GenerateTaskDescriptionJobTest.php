<?php

namespace Tests\Unit\Jobs;

use App\Jobs\CreateTaskInTrackerJob;
use App\Jobs\GenerateTaskDescriptionJob;
use App\Services\TaskDescriptionGenerator\StubDescriptionGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GenerateTaskDescriptionJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_generates_description_and_dispatches_next_job()
    {
        Queue::fake();

        $differenceData = [
            'new_version_id' => 1,
            'new_version_title' => 'Test Page',
            'new_version_content' => 'Test content',
            'is_new_page' => true,
            'diff_output' => '+ Test Page\n+ Test content',
        ];

        $job = new GenerateTaskDescriptionJob($differenceData);
        $job->handle(new StubDescriptionGenerator());

        Queue::assertPushed(CreateTaskInTrackerJob::class, function ($job) {
            return $job->title === 'New page created: Test Page' &&
                   str_contains($job->description, 'Task created from version difference');
        });
    }

    public function test_job_generates_title_for_updated_page_with_title_and_content_changes()
    {
        Queue::fake();

        $differenceData = [
            'new_version_id' => 2,
            'new_version_title' => 'Updated Page',
            'new_version_content' => 'Updated content',
            'old_version_id' => 1,
            'old_version_title' => 'Old Page',
            'old_version_content' => 'Old content',
            'title_changed' => true,
            'content_changed' => true,
            'is_new_page' => false,
        ];

        $job = new GenerateTaskDescriptionJob($differenceData);
        $job->handle(new StubDescriptionGenerator());

        Queue::assertPushed(CreateTaskInTrackerJob::class, function ($job) {
            return $job->title === 'Page updated: Updated Page (title and content changed)';
        });
    }

    public function test_job_generates_title_for_updated_page_with_title_change_only()
    {
        Queue::fake();

        $differenceData = [
            'new_version_id' => 2,
            'new_version_title' => 'Updated Page',
            'new_version_content' => 'Same content',
            'old_version_id' => 1,
            'old_version_title' => 'Old Page',
            'old_version_content' => 'Same content',
            'title_changed' => true,
            'content_changed' => false,
            'is_new_page' => false,
        ];

        $job = new GenerateTaskDescriptionJob($differenceData);
        $job->handle(new StubDescriptionGenerator());

        Queue::assertPushed(CreateTaskInTrackerJob::class, function ($job) {
            return $job->title === 'Page updated: Updated Page (title changed)';
        });
    }

    public function test_job_generates_title_for_updated_page_with_content_change_only()
    {
        Queue::fake();

        $differenceData = [
            'new_version_id' => 2,
            'new_version_title' => 'Same Title',
            'new_version_content' => 'Updated content',
            'old_version_id' => 1,
            'old_version_title' => 'Same Title',
            'old_version_content' => 'Old content',
            'title_changed' => false,
            'content_changed' => true,
            'is_new_page' => false,
        ];

        $job = new GenerateTaskDescriptionJob($differenceData);
        $job->handle(new StubDescriptionGenerator());

        Queue::assertPushed(CreateTaskInTrackerJob::class, function ($job) {
            return $job->title === 'Page updated: Same Title (content changed)';
        });
    }

    public function test_job_uses_diff_output_for_description_generation()
    {
        Queue::fake();

        $differenceData = [
            'new_version_id' => 2,
            'new_version_title' => 'Test Page',
            'new_version_content' => 'New content with multiple lines',
            'old_version_id' => 1,
            'old_version_title' => 'Test Page',
            'old_version_content' => 'Old content',
            'title_changed' => false,
            'content_changed' => true,
            'is_new_page' => false,
            'diff_output' => "- Old content\n+ New content with multiple lines",
        ];

        $job = new GenerateTaskDescriptionJob($differenceData);
        $job->handle(new StubDescriptionGenerator());

        Queue::assertPushed(CreateTaskInTrackerJob::class, function ($job) {
            return $job->title === 'Page updated: Test Page (1 line(s) added, 1 line(s) removed)';
        });
    }

    public function test_job_handles_missing_diff_output_gracefully()
    {
        Queue::fake();

        $differenceData = [
            'new_version_id' => 2,
            'new_version_title' => 'Updated Page',
            'new_version_content' => 'Updated content',
            'old_version_id' => 1,
            'old_version_title' => 'Old Page',
            'old_version_content' => 'Old content',
            'title_changed' => true,
            'content_changed' => true,
            'is_new_page' => false,
        ];

        $job = new GenerateTaskDescriptionJob($differenceData);
        $job->handle(new StubDescriptionGenerator());

        Queue::assertPushed(CreateTaskInTrackerJob::class, function ($job) {
            return $job->title === 'Page updated: Updated Page (title and content changed)';
        });
    }

    public function test_job_generates_title_with_diff_information()
    {
        Queue::fake();

        $differenceData = [
            'new_version_id' => 2,
            'new_version_title' => 'Test Page',
            'new_version_content' => "Line 1\nLine 2\nLine 3",
            'old_version_id' => 1,
            'old_version_title' => 'Test Page',
            'old_version_content' => "Line 1\nLine 2",
            'title_changed' => false,
            'content_changed' => true,
            'is_new_page' => false,
            'diff_output' => "+ Line 3",
        ];

        $job = new GenerateTaskDescriptionJob($differenceData);
        $job->handle(new StubDescriptionGenerator());

        Queue::assertPushed(CreateTaskInTrackerJob::class, function ($job) {
            return $job->title === 'Page updated: Test Page (1 line(s) added)';
        });
    }
}
