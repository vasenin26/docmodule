<?php

namespace Tests\Unit\Jobs;

use App\Common\DTO\DifferenceDataDTO;
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

        $differenceData = new DifferenceDataDTO(
            newVersionTitle: 'Test Page',
            isNewPage: true,
            diffOutput: '+ Test Page\n+ Test content'
        );

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

        $differenceData = new DifferenceDataDTO(
            newVersionTitle: 'Updated Page',
            isNewPage: false,
            titleChanged: true,
            contentChanged: true
        );

        $job = new GenerateTaskDescriptionJob($differenceData);
        $job->handle(new StubDescriptionGenerator());

        Queue::assertPushed(CreateTaskInTrackerJob::class, function ($job) {
            return $job->title === 'Page updated: Updated Page (title and content changed)';
        });
    }

    public function test_job_generates_title_for_updated_page_with_title_change_only()
    {
        Queue::fake();

        $differenceData = new DifferenceDataDTO(
            newVersionTitle: 'Updated Page',
            isNewPage: false,
            titleChanged: true,
            contentChanged: false
        );

        $job = new GenerateTaskDescriptionJob($differenceData);
        $job->handle(new StubDescriptionGenerator());

        Queue::assertPushed(CreateTaskInTrackerJob::class, function ($job) {
            return $job->title === 'Page updated: Updated Page (title changed)';
        });
    }

    public function test_job_generates_title_for_updated_page_with_content_change_only()
    {
        Queue::fake();

        $differenceData = new DifferenceDataDTO(
            newVersionTitle: 'Same Title',
            isNewPage: false,
            titleChanged: false,
            contentChanged: true
        );

        $job = new GenerateTaskDescriptionJob($differenceData);
        $job->handle(new StubDescriptionGenerator());

        Queue::assertPushed(CreateTaskInTrackerJob::class, function ($job) {
            return $job->title === 'Page updated: Same Title (content changed)';
        });
    }

    public function test_job_uses_diff_output_for_description_generation()
    {
        Queue::fake();

        $differenceData = new DifferenceDataDTO(
            newVersionTitle: 'Test Page',
            isNewPage: false,
            titleChanged: false,
            contentChanged: true,
            diffOutput: "- Old content\n+ New content with multiple lines"
        );

        $job = new GenerateTaskDescriptionJob($differenceData);
        $job->handle(new StubDescriptionGenerator());

        Queue::assertPushed(CreateTaskInTrackerJob::class, function ($job) {
            return $job->title === 'Page updated: Test Page (1 line(s) added, 1 line(s) removed)';
        });
    }

    public function test_job_handles_missing_diff_output_gracefully()
    {
        Queue::fake();

        $differenceData = new DifferenceDataDTO(
            newVersionTitle: 'Updated Page',
            isNewPage: false,
            titleChanged: true,
            contentChanged: true
        );

        $job = new GenerateTaskDescriptionJob($differenceData);
        $job->handle(new StubDescriptionGenerator());

        Queue::assertPushed(CreateTaskInTrackerJob::class, function ($job) {
            return $job->title === 'Page updated: Updated Page (title and content changed)';
        });
    }

    public function test_job_generates_title_with_diff_information()
    {
        Queue::fake();

        $differenceData = new DifferenceDataDTO(
            newVersionTitle: 'Test Page',
            isNewPage: false,
            titleChanged: false,
            contentChanged: true,
            diffOutput: "+ Line 3"
        );

        $job = new GenerateTaskDescriptionJob($differenceData);
        $job->handle(new StubDescriptionGenerator());

        Queue::assertPushed(CreateTaskInTrackerJob::class, function ($job) {
            return $job->title === 'Page updated: Test Page (1 line(s) added)';
        });
    }
}
