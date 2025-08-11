<?php

namespace Tests\Unit\Jobs;

use App\Jobs\CreateTaskInTrackerJob;
use App\Services\TaskTracker\Integration\FakeIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateTaskInTrackerJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_creates_task_in_tracker()
    {
        $mockTracker = $this->createMock(FakeIntegration::class);
        $mockTracker->expects($this->once())
            ->method('createTask')
            ->with('Test Task', 'Test Description');

        $job = new CreateTaskInTrackerJob('Test Task', 'Test Description');
        $job->handle($mockTracker);
    }

    public function test_job_handles_empty_description()
    {
        $mockTracker = $this->createMock(FakeIntegration::class);
        $mockTracker->expects($this->once())
            ->method('createTask')
            ->with('Test Task', '');

        $job = new CreateTaskInTrackerJob('Test Task', '');
        $job->handle($mockTracker);
    }

    public function test_job_handles_long_title_and_description()
    {
        $longTitle = str_repeat('A', 1000);
        $longDescription = str_repeat('B', 5000);

        $mockTracker = $this->createMock(FakeIntegration::class);
        $mockTracker->expects($this->once())
            ->method('createTask')
            ->with($longTitle, $longDescription);

        $job = new CreateTaskInTrackerJob($longTitle, $longDescription);
        $job->handle($mockTracker);
    }
}
